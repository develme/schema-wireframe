<?php namespace DevelMe\Console;

use Symfony\Component\Console\Input\InputOption;

/**
 * Model Generator based on Schema
 * @package DevelMe\Console
 * @version 1.0.0
 * @author Verron Knowles <Verron.Knowles@develme.com>
 */
class ModelMakeCommand extends SchemaGeneratorCommand
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:schema-model';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new Eloquent model class based on a database schema';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Model';

    /**
     * Ignore certain things
     *
     * @type Array
     */
    private $ignore_items = array(
        'id', 'updated_at', 'created_at', 'deleted_at', 'password'
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
        return __DIR__.'/../stubs/model/'.$this->getApplicationVersion().'/';;
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

        $stub_contents = file_get_contents($this->getStubDirectory() . 'contents.stub');

        $fillable = array();

        foreach ($stub_data as $stubname => $column) {
            if (empty(strpos($stubname, '&'))) {
                if (!in_array($column['name'], $this->ignore_items, $column['name'])) {
                    $column_name = $column['name'];
                    $fillable[] = "\"$column_name\"";
                }
            }
        }

        $this->replaceTag($stub_contents, 'table', "\"$table_name\"")->replaceTag($stub_contents, 'fillable', implode(",", $fillable));

        return $stub_contents;
    }
    
    /**
     * Get the console options
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @return Array
     */
    protected function getOptions()
    {
        return array(
            array('table', null, InputOption::VALUE_OPTIONAL, 'The name of the table to model after.')
        );
    }
    
}
