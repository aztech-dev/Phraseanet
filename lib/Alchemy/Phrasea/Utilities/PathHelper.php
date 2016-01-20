<?php

namespace Alchemy\Phrasea\Utilities;

use Symfony\Component\Filesystem\Filesystem;

class PathHelper
{
    public static function dispatch(Filesystem $filesystem, $repositoryPath, $date = false)
    {
        if (! $date) {
            $date = date('Y-m-d H:i:s');
        }

        $repositoryPath = \p4string::addEndSlash($repositoryPath);

        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));
        $day = date('d', strtotime($date));

        $n = 0;
        $pathDateSuffix = implode(DIRECTORY_SEPARATOR, [ $year, $month, $day ]);

        do {
            $targetPath = $repositoryPath . $pathDateSuffix . self::addZeros($n);
            $n++;
        } while (is_dir($targetPath) && iterator_count(new \DirectoryIterator($targetPath)) > 100);

        $filesystem->mkdir($targetPath, 0750);

        return \p4string::addEndSlash($targetPath);
    }

    private static function addZeros($n, $length = 5)
    {
        return str_pad($n, $length, '0');
    }
}
