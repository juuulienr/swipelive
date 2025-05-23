<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Clip;
use Aws\MediaConvert\MediaConvertClient;
use Bugsnag\Client;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class VideoProcessor
{
  private readonly MediaConvertClient $mediaConvertClient;

  public function __construct(private readonly EntityManagerInterface $entityManager, private readonly ParameterBagInterface $parameters, private readonly Client $bugsnag)
  {
    $this->mediaConvertClient = new MediaConvertClient([
      'version'     => 'latest',
      'region'      => 'eu-west-3',
      'credentials' => [
        'key'    => $this->parameters->get('mediaconvert_access_key'),
        'secret' => $this->parameters->get('mediaconvert_secret_key'),
      ],
    ]);

    \error_log('VideoProcessor service with Media Convert instantiated.');
  }

  /**
   * Utiliser AWS MediaConvert pour découper une vidéo et envoyer les fichiers découpés sur S3
   *
   * @param Clip $clip L'entité Clip contenant les informations sur le produit et le timing
   *
   * @return bool Retourne true si la découpe et la conversion ont réussi, false sinon
   */
  public function processClip(Clip $clip): bool
  {
    try {
      \error_log('Processing clip ID: ' . $clip->getId());

      $inputFileUrl  = 's3://' . $this->parameters->get('s3_bucket') . '/' . $clip->getLive()->getFileList();
      $outputFileKey = 'vendor' . $clip->getVendor()->getId() . '/Live' . $clip->getLive()->getId() . '/Clip' . $clip->getId() . '/';
      $nameModifier  = '_Clip' . $clip->getId();
      $filename      = \md5(\uniqid());
      $filelist      = $outputFileKey . $filename . $nameModifier . '.m3u8';
      $startTime     = $clip->getStart();
      $endTime       = $clip->getEnd();

      $jobSettings = [
        'Role'     => $this->parameters->get('mediaconvert_role_arn'),
        'Settings' => [
          'Inputs' => [
            [
              'FileInput'      => $inputFileUrl,
              'TimecodeSource' => 'ZEROBASED',
              'InputClippings' => [
                [
                  'StartTimecode' => \sprintf('%02d:%02d:%02d:00', \floor($startTime / 3600), ($startTime / 60) % 60, $startTime % 60),  // Format HH:MM:SS:FF
                  'EndTimecode'   => \sprintf('%02d:%02d:%02d:00', \floor($endTime / 3600), ($endTime / 60) % 60, $endTime % 60),  // Format HH:MM:SS:FF
                ],
              ],
              'AudioSelectors' => [
                'Audio Selector 1' => [
                  'DefaultSelection' => 'DEFAULT',
                  'Tracks'           => [1],
                  'SelectorType'     => 'TRACK',
                ],
              ],
            ],
          ],
          'OutputGroups' => [
            [
              'Name'    => 'HLS Group',
              'Outputs' => [
                [
                  'ContainerSettings' => [
                    'Container' => 'M3U8',
                  ],
                  'VideoDescription' => [
                    'CodecSettings' => [
                      'Codec'        => 'H_264',
                      'H264Settings' => [
                        'RateControlMode'  => 'QVBR',
                        'MaxBitrate'       => 5000000,
                        'GopSize'          => 90,
                        'GopClosedCadence' => 1,
                        'CodecLevel'       => 'AUTO',
                        'CodecProfile'     => 'MAIN',
                      ],
                    ],
                  ],
                  'AudioDescriptions' => [
                    [
                      'CodecSettings' => [
                        'Codec'       => 'AAC',
                        'AacSettings' => [
                          'Bitrate'    => 96000,
                          'CodingMode' => 'CODING_MODE_2_0',
                          'SampleRate' => 48000,
                        ],
                      ],
                    ],
                  ],
                  'NameModifier' => $nameModifier,
                ],
              ],
              'OutputGroupSettings' => [
                'Type'             => 'HLS_GROUP_SETTINGS',
                'HlsGroupSettings' => [
                  'Destination'      => 's3://' . $this->parameters->get('s3_bucket') . '/' . $outputFileKey . '/' . $filename,
                  'SegmentLength'    => 10,
                  'MinSegmentLength' => 1,
                ],
              ],
            ],
          ],
        ],
        // 'AccelerationSettings' => [
        //   'Mode' => 'ENABLED'
        // ],
        'UserMetadata' => [
          'clipId' => $clip->getId(),
        ],
      ];

      $result = $this->mediaConvertClient->createJob($jobSettings);
      $clip->setFileList($filelist);
      $clip->setJobId($result['Job']['Id']);
      $clip->setStatus('progressing');
      $this->entityManager->flush();

      \error_log('Clip updated: ' . $result['Job']['Id']);
    } catch (Exception $e) {
      \error_log('Error progressing clip: ' . $e->getMessage());
      $this->bugsnag->notifyException($e);

      return false;
    }

    return true;
  }
}
