<?php
/**
 * Created by PhpStorm.
 * User: coditoiumihai
 * Date: 21/03/16
 * Time: 12:01
 */

namespace Swagger;

use Swagger\Annotations\Swagger;
use Symfony\Component\Finder\Finder;

class Scan
{
    /**
     * Scan the filesystem for swagger annotations and build swagger-documentation.
     *
     * @param string|array|Finder $directory The directory(s) or filename(s)
     * @param array $options
     *   exclude: string|array $exclude The directory(s) or filename(s) to exclude (as absolute or relative paths)
     *   analyser: defaults to StaticAnalyser
     *   analysis: defaults to a new Analysis
     *   processors: defaults to the registered processors in Analysis
     * @return Swagger
     */
    public static function scan($directory, $options = array())
    {
        $analyser = @$options['analyser'] ?: new StaticAnalyser();
        $analysis = @$options['analysis'] ?: new Analysis();
        $processors = @$options['processors'] ?: Analysis::processors();
        $exclude = @$options['exclude'] ?: null;

        // Crawl directory and parse all files
        $finder = Util::finder($directory, $exclude);
        foreach ($finder as $file) {
            $analysis->addAnalysis($analyser->fromFile($file->getPathname()));
        }
        // Post processing
        $analysis->process($processors);
        // Validation (Generate notices & warnings)
        $analysis->validate();
        return $analysis->swagger;
    }
}