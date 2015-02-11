<?php namespace DevelMe\Console;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * View Generator based on Schema
 * @package DevelMe\Console
 * @version 1.0.0
 * @author Verron Knowles <Verron.Knowles@develme.com>
 */
class ViewMakeCommand extends SchemaGeneratorCommand
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:schema-view';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new resource view class based on MySQL\'s information_schema';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'View';

    /**
     * Ignore certain things
     *
     * @type Array
     */
    private $ignore_items = array('id', 'updated_at', 'created_at', 'deleted_at');

    /**
     * Stubs
     *
     * @type Array
     */
    protected $stub_names = array(
        'index',
        'create',
        'update',
        'read',
    );
    

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return $this->getStubDirectory() . 'base.stub';
	}

    /**
     * Get the stub directory for the generator
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @return string
     */
    protected function getStubDirectory()
    {
        $theme = $this->option('theme');
        $theme_directory = __DIR__."/../themes/$theme/view/";
        $directory = !is_null($theme) && $this->files->isDirectory($theme_directory) ? $theme_directory : __DIR__.'/../stubs/view/';
        
        return $directory;
    }
    
	/**
	 * Parse the name and format according to the root namespace.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function parseName($name)
	{
        return strtolower(snake_case(class_basename($this->original_name))) . "/" .strtolower($name);
	}

    /**
     * Override Get App Namespace command
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @return string
     */
    protected function getPath($name)
    {
        return $this->laravel->basePath()."/resources/views/" . $name . ".blade.php";
    }
    
	/**
	 * Get the desired class name from the input.
	 *
	 * @return string
	 */
	protected function getNameInput()
	{
		return $this->current_name;
	}

    /**
     * Fire event overload for GeneratorCommand
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @return void
     */
    public function fire()
    {
        $this->original_name = $original_name = $this->argument('name');
        $stubs = $this->stub_names;

        foreach ($stubs as $stub_name) {
            $this->current_name = $stub_name;
            
		    $this->info("Creating $stub_name view..");
            parent::fire();
        }
    }
    

    /**
     * Get contents for base.stub
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @return string
     */
    protected function buildClassContents($name, $stub_data)
    {
        $table_name = $this->getTableName($name);
        $simple_name = str_singular($table_name);
        $proper_name = $this->getProperName($simple_name);
        $model_name = $name;
        $layout_master = "layouts.master";
        $layout_content = "content";

        // Build replacement tags
        $replacements = $this->sqlSafeIndexes(compact('proper_name', 'simple_name', 'model_name', 'table_name', 'layout_master', 'layout_content'));
        $this->otherTags = $this->sqlSafeIndexes(compact('layout_master', 'layout_content'));

        $stub_contents = "";
        $view = $this->current_name;
        $stub_location = $this->getStubDirectory() . "$view.stub";
        if ($this->files->isFile($stub_location)) {
            $view_replacements = $this->generateColumnInfo($view, $stub_data, $replacements);  
            $stub_contents .= trim(file_get_contents($stub_location)). "\n";
        }
        //dd($replacements);       
        $replacements = array_merge($replacements, $view_replacements);
        $this->replaceTag($stub_contents, $replacements);

        return $stub_contents;
    }

    /**
     * Keep column titles from SQL table undamaged
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @return Array
     */
    private function sqlSafeIndexes($private_array)
    {
        $change_keys = array();
        foreach ($private_array as $pkey => $pval)
            $change_keys["&$pkey"] = $pval;
        
        return $change_keys;
    }
    

    /**
     * Generate column information
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @return string
     */
    protected function generateColumnInfo($stub, $column_info, $additional_info = array())
    {
        // Setup fillable namespace
        $fillable = array();
        $data_heading = array();
        $data_body = array();
        
        foreach ($column_info as $columnkey => $column) {
            if (empty(strpos($columnkey, '&'))) {
                if (!in_array($column['name'], $this->ignore_items, $column['name'])) {
                    $data_file = $this->getColumnFileData($column);
                    
                    $column = array_merge($column, $additional_info, $this->sqlSafeIndexes(compact('data_file')));
                    $column_name = $column['name'];

                    $data_heading[] = $this->getColumnStubData($stub, 'heading', $column);
                    $data_body[] = $this->getColumnStubData($stub, 'body', $column);
                    $fillable[] = "\"$column_name\"";
                }
            }
        }

        // Build Data_Body combo
        $data_head_body = array();
        foreach ($data_heading as $dkey => $dvalue)
            $data_head_body[] = "$dvalue$data_body[$dkey]";
        
        $fillable = implode(",", $fillable);
        $data_heading = trim(implode("", $data_heading));
        $data_body = trim(implode("", $data_body));
        $data_head_body = trim(implode("", $data_head_body));

        // Build replacement tags
        return $this->sqlSafeIndexes(compact('fillable', 'data_heading', 'data_body', 'data_head_body'));
    }
    
    /**
     * getColumnStubData
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @return string
     */
    protected function getColumnStubData($stub = '', $section, $column)
    {
        $column_data = $this->getStubSection($stub, $section);
        $this->replaceTag($column_data, $column);

        return $column_data;
    }
    
    /**
     * Get column data from column's &file attribute
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @return string
     */
    protected function getColumnFileData($column)
    {
        $column_data = $this->getStubFile($column['&file']);

        // Fix up required
        $column['required'] = $column['required'] ? "requried" : "";

        $this->replaceTag($column_data, $column);

        return $column_data;
    }
    
    /**
     * Get stub heading data
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @return string
     */
    protected function getStubSection($stub, $section)
    {
        $stub_location = $this->getStubDirectory() . "${stub}_data_${section}.stub";
        return file_get_contents($stub_location);
    }
    
    /**
     * Get stub heading data
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @return string
     */
    protected function getStubFile($file)
    {
        $stub_location = $this->getStubDirectory() . "${file}";

        return $this->files->isFile($stub_location) ? trim(file_get_contents($stub_location)) : null;
    }
	
    /**
	 * Get the default namespace for the class.
	 *
	 * @param  string  $rootNamespace
	 * @return string
	 */
	protected function getDefaultNamespace($rootNamespace)
	{
		return 'resource/views';
	}
	
    /**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('table', null, InputOption::VALUE_REQUIRED, 'The name of the table'),
			array('theme', null, InputOption::VALUE_OPTIONAL, 'The theme for the view [bootstrap|foundation|custom]'),
		);
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('name', InputArgument::REQUIRED, 'The name of the model to use with view.'),
		);
	}

}
