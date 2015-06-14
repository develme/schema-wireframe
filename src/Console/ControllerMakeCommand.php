<?php namespace DevelMe\Console;

use Symfony\Component\Console\Input\InputOption;

/**
 * Controller Generator based on Schema
 * @package DevelMe\Console
 * @version 1.0.0
 * @author Verron Knowles <Verron.Knowles@develme.com>
 */
class ControllerMakeCommand extends SchemaGeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:schema-controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new resource controller class based on MySQL\'s information_schema';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Controller';

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
    protected $stub_names = array('construct', 'index', 'create-view', 'create', 'read', 'update-view', 'update', 'delete');


    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->getStubDirectory().'base.stub';
    }

    /**
     * Get the stub directory for the generator
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @return string
     */
    protected function getStubDirectory()
    {
        return __DIR__.'/../stubs/controller/'.$this->getApplicationVersion().'/';
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
        $model_name = $this->input->getOption('model');

        // Setup fillable namespace
        $fillable = array();
        foreach ($stub_data as $stubname => $column) {
            if (empty(strpos($stubname, '&'))) {
                if (!in_array($column['name'], $this->ignore_items, $column['name'])) {
                    $column_name = $column['name'];
                    $fillable[] = "\"$column_name\"";
                }
            }
        }
        $fillable = implode(",", $fillable);

        $stub_contents = "";

        foreach ($this->stub_names as $view) {
            $stub_location = $this->getStubDirectory() . "$view.stub";
            if ($this->files->isFile($stub_location)) {
                $stub_contents .= file_get_contents($stub_location). "\n";
            }
        }

        // Build replacement tags
        $replacements = compact('proper_name', 'simple_name', 'model_name', 'table_name', 'fillable');

        $this->replaceTag($stub_contents, $replacements);

        return $stub_contents;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Controllers';
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
            array('model', null, InputOption::VALUE_REQUIRED, 'The name of the model'),
        );
    }

}
