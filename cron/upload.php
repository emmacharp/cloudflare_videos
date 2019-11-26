<?php

use function GuzzleHttp\json_decode;

define('CURRENT_FOLDER', '\cron');
    define('RELROOT', '\extensions\cloudflare_videos\cron');
    define('DOCROOT', str_replace(RELROOT, '', rtrim(dirname(__FILE__), '\\/') ));
    define('EXTENSION_ROOT', str_replace(CURRENT_FOLDER, '', rtrim(dirname(__FILE__), '\\/') ));

    require_once(EXTENSION_ROOT . '/vendor/autoload.php');
    require_once(DOCROOT . '/vendor/autoload.php');
    require_once(DOCROOT . '/symphony/lib/boot/bundle.php');

    Administration::instance();

    $config = Symphony::Configuration()->get('cloudflare_videos');

    $tusOptions = array(
        'headers' => array(
            'X-Auth-Email' => $config['email'],
            'X-Auth-Key' => $config['api-key'],
        ),
    );

    $fields = FieldManager::select()
                ->where([
                    'type' => 'cloudflare_video'
                ])
                ->execute()
                ->rows();

    foreach ($fields as $field) {
        $unprocessedVideos = Symphony::Database()
                                ->select()
                                ->from('sym_entries_data_' . $field->get('id'))
                                ->where([
                                    'processed' => 'no'
                                ])
                                ->execute()
                                ->rows();

        foreach ($unprocessedVideos as $unprocessedVideo) {

            if ($unprocessedVideo['uploaded'] === 'no') {
                $key = uniqid();

                var_dump('hello world');
                die();

                $client = new \TusPhp\Tus\Client('https://api.cloudflare.com/client/v4/zones/' . $config['zone-id'] . '/stream', $tusOptions);
                $client->setKey($key);
                $client->setApiPath('/client/v4/zones/' . $config['zone-id'] . '/stream');
                $client->file(EXTENSION_ROOT . '\test\video.webm', 'video.webm');

                $client->setMetadata(array(
                    'filename' => 'video.webm',
                    'filetype' => 'video/webm',
                ));

                $unprocessedVideo['video_url'] = $client->create($key);
                $unprocessedVideo['uploaded'] = 'yes';

                $client->upload();
            }

            $entry = (new EntryManager)->select()->entry($unprocessedVideo['entry_id'])->execute()->next();

            if (empty($entry)) {
                continue;
            }

            $section = (new SectionManager)
                            ->select()
                            ->section($entry->get('section_id'))
                            ->execute()
                            ->next();

            if (empty($section)) {
                continue;
            }

            $ch = new Gateway();
            $ch->init($unprocessedVideo['video_url']);
            $ch->setopt('HTTPHEADER', array(
                'X-Auth-Email: ' . $config['email'],
                'X-Auth-Key: ' . $config['api-key'],
            ));

            $cloudflareData = $ch->exec();
            $cloudflareData = json_decode($cloudflareData, JSON_FORCE_OBJECT);

            if ($cloudflareData['readyToStream'] === true) {
                $unprocessedVideo['processed'] = 'yes';
            }

            $unprocessedVideo['meta'] = json_encode($cloudflareData);

            $f = array();

            Symphony::ExtensionManager()->notifyMembers('EntryPreEdit', '/publish/edit/', array(
                'section' => $section,
                'entry' => &$entry,
                'fields' => $f
            ));

            $updateWorked = Symphony::Database()
                        ->update('sym_entries_data_' . $field->get('id'))
                        ->where(['id' => $unprocessedVideo['id']])
                        ->set($unprocessedVideo)
                        ->execute()
                        ->success();

            if ($updateWorked) {
                Symphony::ExtensionManager()->notifyMembers('EntryPostEdit', '/publish/edit/', array(
                    'section' => $section,
                    'entry' => $entry,
                    'fields' => $f
                ));
            }
        }
    }

return true;
