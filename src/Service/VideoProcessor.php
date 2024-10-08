<?php

namespace App\Service;

use App\Entity\Clip;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Aws\S3\S3Client;

class VideoProcessor
{
  private $entityManager;
  private $s3Client;
  private $parameters;

  public function __construct(EntityManagerInterface $entityManager, S3Client $s3Client, ParameterBagInterface $parameters)
  {
    $this->entityManager = $entityManager;
    $this->parameters = $parameters;
    $this->s3Client = $s3Client;
  }

  /**
   * Découpe une vidéo à partir d'un fichier M3U8 distant et envoie les fichiers découpés sur AWS S3
   *
   * @param Clip $clip L'entité Clip contenant les informations sur le produit et le timing
   * @return bool Retourne true si la découpe et la conversion ont réussi, false sinon
   */
  public function processClip(Clip $clip): bool
  {
      // Récupérer l'URL du fichier M3U8 depuis S3
    $fileUrl = 'https://' . $this->parameters->get('s3_bucket') . '.s3.eu-west-3.amazonaws.com/' . $clip->getLive()->getFileList();

    // Créer un répertoire temporaire pour stocker les segments découpés
    $outputDir = '/tmp/clip_' . $clip->getId();  // Dossier temporaire sur Heroku pour les segments
    $fileList = md5(uniqid()) . '_Clip' . $clip->getId() . '.m3u8';
    $outputFile = $outputDir . '/' . $fileList;

    // Convertir les timestamps en format H:i:s
    $start = gmdate("H:i:s", $clip->getStart());  // Timestamp de début
    $end = gmdate("H:i:s", $clip->getEnd());      // Timestamp de fin

    // Récupérer la durée réelle de la vidéo (optionnel si le fichier M3U8 est valide)
    $videoDuration = $this->getVideoDuration($fileUrl);
    if ($clip->getEnd() > $videoDuration) {
      $end = gmdate("H:i:s", $videoDuration);
    }

    // Appel de la commande FFmpeg pour découper et convertir le fichier M3U8 distant
    $command = sprintf(
      'ffmpeg -i %s -ss %s -to %s -hls_time 10 -hls_playlist_type vod -hls_segment_filename "%s/segment_%%03d.ts" %s',
      escapeshellarg($fileUrl),
      escapeshellarg($start),
      escapeshellarg($end),
      escapeshellarg($outputDir),
      escapeshellarg($outputFile)
    );

    exec($command, $output, $returnVar);

    if ($returnVar !== 0) {
      return false;  // Échec de la conversion et du découpage
    }

    $key = 'vendor' . $clip->getVendor()->getId() . '/Live' . $clip->getLive()->getId() . '/Clip' . $clip->getId() . $fileList;

  // Upload des fichiers M3U8 et des segments découpés sur S3
    $this->uploadDirectoryToS3($outputFile, $key);

  // Mettre à jour le chemin du fichier M3U8 dans l'entité Clip
    $clip->setFileList($fileList);
    $clip->setStatus('découpé');
    $this->entityManager->flush();

    return true;
  }

  /**
   * Récupérer la durée d'une vidéo avec FFmpeg
   */
  private function getVideoDuration($filePath): ?int
  {
    $command = sprintf('ffmpeg -i %s 2>&1 | grep "Duration"', escapeshellarg($filePath));
    $output = shell_exec($command);

    if (preg_match('/Duration: (\d+):(\d+):(\d+\.\d+)/', $output, $matches)) {
      $hours = (int) $matches[1];
      $minutes = (int) $matches[2];
      $seconds = (float) $matches[3];
      return ($hours * 3600) + ($minutes * 60) + $seconds;
    }

    return null;
  }

  /**
   * Upload des fichiers découpés (M3U8 et segments .ts) sur S3
   *
   * @param string $directoryPath Le chemin du dossier temporaire contenant les fichiers M3U8 et .ts
   * @param string $s3Key Le chemin du répertoire de destination sur S3
   */
  private function uploadDirectoryToS3($directoryPath, $s3Key): void
  {
      // Ouvrir le dossier temporaire
    $files = scandir($directoryPath);
    foreach ($files as $file) {
      if ($file !== '.' && $file !== '..') {
        $filePath = $directoryPath . '/' . $file;
        $this->uploadToS3($filePath, $s3Key . '/' . $file);
      }
    }
  }

  /**
   * Upload d'un fichier unique sur S3
   */
  private function uploadToS3($filePath, $key): void
  {
    try {
      $this->s3Client->putObject([
        'Bucket' => $this->parameters->get('s3_bucket'),
        'Key' => $key,
        'SourceFile' => $filePath,
        'ACL' => 'public-read',
        'ContentType' => $this->getMimeType($filePath)
      ]);
    } catch (\Exception $e) {
      error_log('Error uploading to S3: ' . $e->getMessage());
    }
  }

  /**
   * Obtenir le type MIME en fonction du fichier
   */
  private function getMimeType($filePath): string
  {
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    switch ($ext) {
      case 'm3u8':
      return 'application/vnd.apple.mpegurl';
      case 'ts':
      return 'video/MP2T';
      default:
      return 'application/octet-stream';
    }
  }
}
