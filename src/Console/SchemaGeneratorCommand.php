<?php namespace DevelMe\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Container\Container;
use Illuminate\Database\DatabaseManager;
use Illuminate\Config\Repository as ConfigRepository;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Schema Command for DevelMe's Generators
 * @package DevelMe\Console
 * @version 1.0.0
 * @author Verron Knowles <Verron.Knowles@develme.com>
 */
abstract class SchemaGeneratorCommand extends GeneratorCommand
{
    /**
     * Laravel Container
     *
     * @type Container
     */
    protected $app;

    /**
     * Laravel FileSystem
     *
     * @type FileSystem
     */
    protected $files;

    /**
     * Laravel Database Manager
     *
     * @type DatabaseManager
     */
    protected $database;

    /**
     * Laravel Configuration Repository
     *
     * @type ConfigRepository
     */
    protected $config;

    /**
     * Other tags for base.stub build
     *
     * @type Array
     */
    protected $otherTags = array();

    /**
     * Stub Map Conversions
     *
     * @type Array
     */
    protected $stub_map = array(
        'input' => array(
            'int', 'tinyint', 'smallint', 'mediumint',
            'bigint', 'float', 'double', 'decimal',
            'date', 'datetime', 'time', 'year',
            'varchar', 'timestamp'),
        'textarea' => array('blobl', 'text', 'tinyblod', 'tinytext', 'longblob', 'longtext'),
        'select' => array('enum')
    );

    /**
     * Databse Type Map Conversion
     *
     * @type Array
     */
    protected $type_map = array(
        'number' => array(
            'int', 'tinyint', 'smallint', 'mediumint',
            'bigint', 'float', 'double', 'decimal'
        ),
        'text' => array(
            'varchar', 'blob', 'text', 'tinyblod',
            'tinytext', 'longblob', 'longtext'
        ),
        'date' => array('date', 'year', 'datetime', 'timestamp'),
        'time' => array('time'),
        //'datetime' => array('datetime', 'timestamp'),
        'radio' => array('enum'),
        'password' => array(),
        'submit' => array(),
        'checkbox' => array(),
        'button' => array(),
        'color' => array(),
        'range' => array(),
        'month' => array(),
        'week' => array(),
        'email' => array(),
        'search' => array(),
        'tel' => array(),
        'url' => array()
    );


    /**
     * Command Defaults
     *
     * @type Array
     */
    protected $defaults = array(
        'path' => array(
            'views' => 'resources/views',
            'models' => 'app',
            'controllers' => 'app/Http/Controllers'
        )
    );

    /**
     * Constructor
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @param $container
     * @param $files
     * @param $database
     * @param $config
     * @return void
     */
    public function __construct(Container $container, FileSystem $files, DatabaseManager $database, ConfigRepository $config)
    {
        parent::__construct($files);

        $this->app = $container;

        $this->database = $database;

        $this->config = $config;

    }

    /**
     * Build Model Class
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @param $name
     * @return void
     */
    protected function buildClass($name)
    {
        // Schema information
        $table_name = $this->getTableName($name);
        $columns_info = $this->getTableColumns($table_name);

        // Get stub data for class contents
        $stub_data = ($this->getStubData($columns_info));

        // Get class contents for the stub
        $contents = $this->buildClassContents($name, $stub_data);

        // Let Laravel do the basics
        $parentBuild = parent::buildClass($name);

        $replacements = array_merge(compact('contents'), $this->otherTags);
        // Insert contents
        $this->replaceTag($parentBuild, $replacements);

        // Hand off to Laravel to finish
        return $parentBuild;
    }

    /**
     * Build content for base.stub file
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @param $stub_data
     * @return string
     */
    abstract protected function buildClassContents($name, $stub_data);

    /**
     * Get stub data
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @param $columns_info
     * @return Array
     */
    private function getStubData($columns_info)
    {
        return array_map(function($column) {
            // Prerequisites
            $stub_data = array();
            $column_name = $column->column_name;
            $data_type = $column->data_type;

            // Set stub data
            $stub_data['name'] = $column_name;
            $stub_data['title'] = $this->getProperName($column_name);
            $stub_data['type'] = $this->findMap($data_type, $this->type_map);
            $stub_data['comment'] = $column->column_comment;
            $stub_data['required'] = $column->is_nullable == "NO" ? true : false;

            // Get stub file
            $stub_data['&file'] = str_finish($this->findMap($data_type, $this->stub_map), '.stub');

            return $stub_data;
        }, $columns_info);


    }


    /**
     * Generate proper title
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @param $column_name
     * @return void
     */
    public function getProperName($column_name)
    {
        return implode(' ', array_map('ucfirst', explode('_', snake_case($column_name))));
    }


    /**
     * Deep array_search
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @param $needle
     * @param $haystack
     * @return bool
     */
    private function findMap($needle, $haystack, $updatedkey = null )
    {
        foreach ($haystack as $key => $val ) {
            if (is_null($updatedkey)) $return_key = $key;
            if ($needle === $val || (is_array($val) && $this->findMap($needle, $val, true)))
                return isset($return_key) ? $return_key: true;
        }

        return false;
    }


    /**
     * Get Table Columns
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @param $table
     * @return Array
     */
    public function getTableColumns($table)
    {
        $default_connection = $this->config->get('database.default');

        $column_where = [
            'table_name' => $table,
            'table_schema' => $this->config->get('database.connections.'.$default_connection.'.database'),
        ];

        $column_select = [
            'column_name',
            'data_type',
            'column_key',
            'is_nullable',
            'extra',
            'column_comment',
        ];

        return $this->database->connection('schema')->table('columns')->where($column_where)->select($column_select)->get();
    }

    /**
     * Get Table Name
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @return string
     */
    protected function getTableName($name)
    {
        $opt_table = $this->input->getOption('table');

        if (!$opt_table && $this->type === "Controller") $opt_table = str_plural(str_replace('controller', '', strtolower($this->getClassName($name))));
        return $opt_table ?: strtolower(str_plural($this->getClassName($name)));
    }

    /**
     * Get application version
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @return String
     */
    protected function getApplicationVersion()
    {
        $app = $this->app;
        preg_match_all('!\d+!', $app::VERSION, $versions);

        return substr(implode(".", array_flatten($versions)), 0, 3);
    }

    /**
     * Get the class name
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @param $name
     * @return void
     */
    public function getClassName($name)
    {
        return str_replace($this->getNamespace($name).'\\', '', $name);
    }

    /**
     * Replace tag within stub
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @return SchemaGeneratorCommand
     */
    public function replaceTag(&$stub, $tag, $value = null)
    {
        if (!is_array($tag) && !is_null($value)) {
            $replacing = array("{{{$tag}}}");
            $replacewith = array($value);
        } else if (is_array($tag)){
            $replacing = array();
            $replacewith = array();

            foreach ($tag as $replacee => $replacer) {
                $replacing[] = "{{{$replacee}}}";
                $replacewith[] = $replacer;
            }
        }
        $stub = str_replace($replacing, $replacewith, $stub);

        return $this;
    }

}
