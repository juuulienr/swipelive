<?php

namespace App\Service;

use App\Entity\Clip;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Aws\MediaConvert\MediaConvertClient;
use Aws\S3\S3Client;

class VideoProcessor
{
  private $entityManager;
  private $mediaConvertClient;
  private $parameters;
  private $bugsnag;

  public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameters, \Bugsnag\Client $bugsnag)
  {
    $this->entityManager = $entityManager;
    $this->parameters = $parameters;
    $this->bugsnag = $bugsnag;
    $this->mediaConvertClient = new MediaConvertClient([
      'version' => 'latest',
      'region' => 'eu-west-3',
      'credentials' => [
        'key' => $this->parameters->get('mediaconvert_access_key'),
        'secret' => $this->parameters->get('mediaconvert_secret_key'),
      ],
    ]);

    error_log('VideoProcessor service with Media Convert instantiated.');
  }

  /**
   * Utiliser AWS MediaConvert pour découper une vidéo et envoyer les fichiers découpés sur S3
   *
   * @param Clip $clip L'entité Clip contenant les informations sur le produit et le timing
   * @return bool Retourne true si la découpe et la conversion ont réussi, false sinon
   */
  public function processClip(Clip $clip): bool
  {
    try {
      // Log au début de l'exécution de processClip
      error_log('Processing clip ID: ' . $clip->getId());

      // URL de la vidéo source dans S3
      $inputFileUrl = 's3://' . $this->parameters->get('s3_bucket') . '/' . $clip->getLive()->getFileList();
      error_log('Url du fichier video : ' . $inputFileUrl);

      // Chemin de sortie pour le fichier M3U8 et les segments TS
      $outputFileKey = 'vendor' . $clip->getVendor()->getId() . '/Live' . $clip->getLive()->getId() . '/Clip' . $clip->getId() . '/';
      $nameModifier = '_Clip' . $clip->getId(); 
      $filename = md5(uniqid());
      $filelist = $outputFileKey . $filename . $nameModifier . '.m3u8';
      $startTime = $clip->getStart();
      $endTime = $clip->getEnd();

      $jobSettings = [
        'Role' => $this->parameters->get('mediaconvert_role_arn'),  
        'Settings' => [
          // 'TimecodeConfig' => [  // Ajout du paramètre TimecodeConfig
          //   'Source' => 'ZEROBASED'
          // ],
          'Inputs' => [
            [
              'FileInput' => $inputFileUrl, 
              'TimecodeSource' => 'ZEROBASED',
              'InputClippings' => [
                [
                  'StartTimecode' => sprintf('%02d:%02d:%02d:00', floor($startTime / 3600), ($startTime / 60) % 60, $startTime % 60),  // Format HH:MM:SS:FF
                  'EndTimecode' => sprintf('%02d:%02d:%02d:00', floor($endTime / 3600), ($endTime / 60) % 60, $endTime % 60),  // Format HH:MM:SS:FF
                ]
              ],
            ]
          ],
          'OutputGroups' => [
            [
              'Name' => 'HLS Group',
              'Outputs' => [
                [
                  'ContainerSettings' => [
                    'Container' => 'M3U8'
                  ],
                  'VideoDescription' => [
                    'CodecSettings' => [
                      'Codec' => 'H_264',
                      'H264Settings' => [
                        'RateControlMode' => 'QVBR',   // QVBR pour qualité variable
                        'MaxBitrate' => 5000000,       // MaxBitrate, sans Bitrate
                        'GopSize' => 90,
                        'GopClosedCadence' => 1,
                        'CodecLevel' => 'AUTO',
                        'CodecProfile' => 'MAIN'
                      ]
                    ]
                  ],
                  'NameModifier' => $nameModifier,  // Suffixe pour modifier le nom des fichiers de sortie
                ]
              ],
              'OutputGroupSettings' => [
                'Type' => 'HLS_GROUP_SETTINGS',  // Type de groupe HLS
                'HlsGroupSettings' => [
                  'Destination' => 's3://' . $this->parameters->get('s3_bucket') . '/' . $outputFileKey . '/' . $filename,
                  'SegmentLength' => 10,
                  'MinSegmentLength' => 1  // Longueur minimale des segments (1 seconde)
                ]
              ]
            ]
          ]
        ],
        // 'AccelerationSettings' => [  // Activer l'accélération
        //   'Mode' => 'ENABLED'     // 'ENABLED' pour activer l'accélération
        // ],
        'UserMetadata' => [
          'clipId' => $clip->getId()
        ],
        'Queue' => 'arn:aws:mediaconvert:eu-west-3:600627343574:queues/Default',
        'Notification' => [  // Notification via SNS
          'SnsTopicArn' => 'arn:aws:sns:eu-west-3:600627343574:MediaConvertNotifications:094289b6-36cf-4e47-811d-ebafc7baf77c'
        ]
      ];

      $result = $this->mediaConvertClient->createJob($jobSettings);

      // Log l'ID du job MediaConvert
      error_log('MediaConvert Job ID: ' . $result['Job']['Id']);

      // Enregistrer le chemin du fichier M3U8 dans l'entité Clip
      $clip->setFileList($filelist);
      $clip->setStatus('progressing');
      $this->entityManager->flush();

      error_log('Clip updated');

    } catch (\Exception $e) {
      error_log('Error progressing clip: ' . $e->getMessage());
      $this->bugsnag->notifyException($e);
      return false;
    }

    return true;
  }
}
