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
  private $bugsnag;

  public function __construct(EntityManagerInterface $entityManager, S3Client $s3Client, ParameterBagInterface $parameters, \Bugsnag\Client $bugsnag)
  {
    $this->entityManager = $entityManager;
    $this->parameters = $parameters;
    $this->s3Client = $s3Client;
    $this->bugsnag = $bugsnag;

        // Log when the constructor is called
    error_log('VideoProcessor service instantiated.');
  }

  /**
   * Découpe une vidéo à partir d'un fichier M3U8 distant et envoie les fichiers découpés sur AWS S3
   *
   * @param Clip $clip L'entité Clip contenant les informations sur le produit et le timing
   * @return bool Retourne true si la découpe et la conversion ont réussi, false sinon
   */
  public function processClip(Clip $clip): bool
  {
    try {
          // Log au début de l'exécution de processClip 
      error_log('Processing clip ID: ' . $clip->getId()); 

          // Récupérer l'URL du fichier M3U8 depuis S3
      $fileUrl = 'https://' . $this->parameters->get('s3_bucket') . '.s3.eu-west-3.amazonaws.com/' . $clip->getLive()->getFileList();

          // Log avant d'exécuter la commande FFmpeg
      error_log('File URL: ' . $fileUrl);

          // Générer un nom unique pour le fichier M3U8
      $uniqueFileName = md5(uniqid()) . '_Clip' . $clip->getId() . '.m3u8';

          // Chemin S3 où les segments et le fichier M3U8 seront stockés
      $bucket = $this->parameters->get('s3_bucket');
      $segmentKey = 'vendor' . $clip->getVendor()->getId() . '/Live' . $clip->getLive()->getId() . '/Clip' . $clip->getId() . '/segment_%03d.ts';
      $m3u8Key = 'vendor' . $clip->getVendor()->getId() . '/Live' . $clip->getLive()->getId() . '/Clip' . $clip->getId() . '/' . $uniqueFileName;

          // Convertir les timestamps en format H:i:s
      $start = gmdate("H:i:s", $clip->getStart());
      $end = gmdate("H:i:s", $clip->getEnd());
 
          // Log avant d'exécuter la commande FFmpeg
      error_log('FFmpeg command: Start ' . $start . ', End ' . $end);

          // Utilisation de S3 pour les segments et le fichier M3U8
      $command = sprintf(
        'ffmpeg -loglevel debug -i %s -ss %s -to %s -threads 1 -hls_time 10 -hls_playlist_type vod -hls_segment_filename "s3://%s/%s" "s3://%s/%s"',
        escapeshellarg($fileUrl),
        escapeshellarg($start),
        escapeshellarg($end),
        escapeshellarg($bucket),
        escapeshellarg($segmentKey),
        escapeshellarg($bucket),
        escapeshellarg($m3u8Key)
      );

      exec($command, $output, $returnVar);

          // Log du résultat de la commande FFmpeg
      error_log('FFmpeg command return value: ' . $returnVar);

      if ($returnVar !== 0) {
        error_log('FFmpeg command failed.');
        return false;
      }

          // Mise à jour de l'entité Clip avec le chemin S3 du fichier M3U8
      $clip->setFileList($m3u8Key);
      $clip->setStatus('découpé');
      $this->entityManager->flush();

      error_log('Clip updated with status "découpé".');

    } catch (\Exception $e) {
          // Log en cas d'erreur
      error_log('Error processing clip: ' . $e->getMessage());
      $this->bugsnag->notifyException($e);
    }

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
