<?php

namespace SourceBroker\InstanceManager\Helper;

class InitHelper
{
    public static function createSymlinks()
    {

        // TODO: Make the path creating better. To work inside vendors and outside.
        $source = __DIR__ . '/../../';
        $targetWeb = $target = __DIR__ . '/../../../../../';
        if (is_dir($target . 'web')) {
            $targetWeb .= 'web/';
        }

        if (!is_dir($target . 'bin')) {
            mkdir($target . 'bin');
        }

        if (file_exists($target . 'bin/im') || is_link($target . 'bin/im')) {
            unlink($target . 'bin/im');
        }
        symlink($source . 'app/console', $target . 'bin/im');

        if (file_exists($targetWeb . 'index_im.php') || is_link($targetWeb . 'index_im.php')) {
            unlink($targetWeb . 'index_im.php');
        }
        symlink($source . 'web/index.php', $targetWeb . 'index_im.php');
    }
}