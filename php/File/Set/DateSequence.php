<?php

require_once 'Set.php';
require_once 'File/Find/Rule.php';

class File_Set_DateSequence extends Set
{
    protected $directory; // A directory that contains all the files in the set.
    protected $separator = '-'; // Separates the date parts.
    protected $suffix; // A file name suffix.
    // Maybe TODO: Add an optional prefix?
    protected $date_format = 'Y-m-d-H-i-s'; // For the PHP date() function.
    protected $regex; // Based on all the other attributes, this becomes a predicate that members must satisfy.
    
    function __construct(array $params = array())
    {
        // TODO: Add a locking mechanism for the directory.
        if (array_key_exists('directory', $params)) {
            $directory = $params['directory'];
            if (!is_dir( $directory )) {
                throw new Exception("Directory '$directory' does not exist");
            }
            $this->directory = $directory;
        } else {
            $this->directory = getcwd();
        }

        if (array_key_exists('separator', $params)) {
            $separator = $params['separator'];
            if (!is_scalar( $separator )) {
                throw new Exception("Param 'separator' must be a scalar");
            }
            $this->separator = $separator;
        }

        if (array_key_exists('suffix', $params)) {
            $suffix = $params['suffix'];
            if (!is_scalar($suffix)) {
                throw new Exception("Param 'suffix' must be a scalar");
            }
            $this->suffix = $suffix;
        }

        $this->regex = $this->generate_regex(
            $this->separator,
            $this->suffix
        );

        $f = new File_Find_Rule();
        $file_names = $f->name( $this->regex )->in( $this->directory );
        parent::__construct();
        foreach ($file_names as $file_name) {
            // This is fragile! Only works to add files this way
            // in the constructor, because we're adding files that
            // already exist.
            parent::add( $file_name );
        }
    }

    protected function generate_regex($separator, $suffix)
    {
        $date_parts = array(
            '/^\d{4}', // year
            '\d{2}', // month (01-12)
            '\d{2}', // day (01-31)
            '\d{2}', // hour (00-23)
            '\d{2}', // minutes (00-59)
            '\d{2}', // seconds (00-59)
        );
        $regex = join($date_parts, $separator);
        if ($suffix) {
            $suffix_regex = quotemeta( $suffix );
            $regex .= $suffix_regex;
        }
        $regex .= '$/';
        return $regex;
    }

    public function add()
    {
        $args = func_get_args();
        if (array_key_exists('0', $args)) {
            $file_name = $args[0];
            if (file_exists($file_name)) {
                throw new Exception("File '$file_name' already exists");
            }
            $basename = basename( $file_name );
            if (!preg_match($this->regex, $basename)) {
                throw new Exception(
                    "File '$file_name' fails to satisfy predicate " . $this->regex
                );
            }
            $dirname = dirname( $file_name );
            if ($dirname == '.') {
                $file_name = $this->directory . '/' . $file_name;
            } else if ($dirname != $this->directory) {
                throw new Exception("Invalid directory '$dirname'");
            }
        } else {
            unset($file_name);
            foreach (range(0,60) as $i) {
                $file_name = $this->directory . '/' . date( $this->date_format );
                if ($this->suffix) {
                    $file_name .= $this->suffix;
                }
                if (file_exists( $file_name )) {
                    unset($file_name);
                    sleep(1);
                    continue;
                } else {
                    break;
                }
            }
            if (!$file_name) {
                throw new Exception("Possible race condition: cannot create unique file name");
            }
        }
        touch( $file_name );
        parent::add( $file_name );
        return $file_name;
    }

    public function delete( $file_name )
    {
        if (!file_exists($file_name)) {
            throw new Exception("File '$file_name' does not exist");
        }
        unlink( $file_name );
        parent::delete( $file_name );
    }

/* Don't think I need this, but keeping it here for now...
    protected function sort($file_names) {
        // natcasesort preserves keys. We need to break that:
        natcasesort( $file_names );
        $sorted_file_names = array();
        foreach ($file_names as $file_name) {
            $sorted_file_names[] = $file_name;
        }
        return $sorted_file_names;
    }
*/
    
} // end class File_Set_DateSequence
