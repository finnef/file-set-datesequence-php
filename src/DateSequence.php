<?php

namespace UmnLib\Core\File\Set;

use Symfony\Component\Finder\Finder;

class DateSequence extends \UmnLib\Core\Set
{
    protected $directory; // A directory that contains all the files in the set.
    protected $separator = '-'; // Separates the date parts.
    protected $suffix; // A file name suffix.
    // Maybe TODO: Add an optional prefix?
    protected $dateFormat = 'Y-m-d-H-i-s'; // For the PHP date() function.
    protected $regex; // Based on all the other attributes, this becomes a predicate that members must satisfy.
    
    function __construct(array $params = array())
    {
        // TODO: Add a locking mechanism for the directory.
        if (array_key_exists('directory', $params)) {
            $directory = $params['directory'];
            if (!is_dir($directory)) {
                throw new \InvalidArgumentException("Directory '$directory' does not exist");
            }
            $this->directory = $directory;
        } else {
            $this->directory = getcwd();
        }

        if (array_key_exists('separator', $params)) {
            $separator = $params['separator'];
            if (!is_scalar($separator)) {
                throw new \InvalidArgumentException("Param 'separator' must be a scalar");
            }
            $this->separator = $separator;
        }

        if (array_key_exists('suffix', $params)) {
            $suffix = $params['suffix'];
            if (!is_scalar($suffix)) {
                throw new \InvalidArgumentException("Param 'suffix' must be a scalar");
            }
            $this->suffix = $suffix;
        }

        $this->regex = $this->generateRegex(
            $this->separator,
            $this->suffix
        );

        //$f = new File_Find_Rule();
        //$filenames = $f->name( $this->regex )->in( $this->directory );
        $finder = new Finder();
        $files = $finder->name($this->regex)->in($this->directory);
        parent::__construct();
        foreach ($files as $file) {
            // This is fragile! Only works to add files this way
            // in the constructor, because we're adding files that
            // already exist.
            parent::add($file->getFilename());
        }
    }

    protected function generateRegex($separator, $suffix)
    {
        $dateParts = array(
            '/^\d{4}', // year
            '\d{2}', // month (01-12)
            '\d{2}', // day (01-31)
            '\d{2}', // hour (00-23)
            '\d{2}', // minutes (00-59)
            '\d{2}', // seconds (00-59)
        );
        $regex = join($dateParts, $separator);
        if ($suffix) {
            $suffixRegex = quotemeta($suffix);
            $regex .= $suffixRegex;
        }
        $regex .= '$/';
        return $regex;
    }

    public function add()
    {
        $args = func_get_args();
        if (array_key_exists('0', $args)) {
            $filename = $args[0];
            if (file_exists($filename)) {
                throw new \InvalidArgumentException("File '$filename' already exists");
            }
            $basename = basename($filename);
            if (!preg_match($this->regex, $basename)) {
                throw new \InvalidArgumentException(
                    "File '$filename' fails to satisfy predicate " . $this->regex
                );
            }
            $dirname = dirname($filename);
            if ($dirname == '.') {
                $filename = $this->directory . '/' . $filename;
            } else if ($dirname != $this->directory) {
                throw new \InvalidArgumentException("\Invalid directory '$dirname'");
            }
        } else {
            unset($filename);
            foreach (range(0,60) as $i) {
                $filename = $this->directory . '/' . date($this->dateFormat);
                if ($this->suffix) {
                    $filename .= $this->suffix;
                }
                if (file_exists($filename)) {
                    unset($filename);
                    sleep(1);
                    continue;
                } else {
                    break;
                }
            }
            if (!$filename) {
                throw new \RuntimeException("Possible race condition: cannot create unique file name");
            }
        }
        touch($filename);
        parent::add($filename);
        return $filename;
    }

    public function delete($filename)
    {
        if (!file_exists($filename)) {
            throw new \InvalidArgumentException("File '$filename' does not exist");
        }
        unlink($filename);
        parent::delete($filename);
    }

/* Don't think I need this, but keeping it here for now...
    protected function sort($filenames) {
        // natcasesort preserves keys. We need to break that:
        natcasesort($filenames);
        $sortedFileNames = array();
        foreach ($filenames as $filename) {
            $sortedFileNames[] = $filename;
        }
        return $sortedFileNames;
    }
*/
    
}
