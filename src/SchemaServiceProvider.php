<?php

namespace DevelMe;

use Illuminate\Support\ServiceProvider;

class SchemaServiceProvider extends ServiceProvider
{
    /**
     * Schema Wireframe Commands
     *
     * @type Array
     */
    private $commands = [
        'ControllerMake',
        'ModelMake',
        'SchemaAppMake',
        'ViewMake'
    ];
    
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
    }

    /**
     * Register all of the schema-wireframe commands.
     *
     * @return void
     */
    private function registerCommands()
    {
        $commands = $this->commands;
        $bind_names = [];

        foreach ($commands as $command) {
            $this->registerCommand($command);

            $bind_name = strtolower($command);
            $bind_names[] = "command.schema.$bind_name";
        }

        call_user_func_array([$this, 'commands'], $bind_names);
    }

    /**
     * Register ControllerMake Command
     *
     * @author Verron Knowles <Verron.Knowles@develme.com>
     * @return void
     */
    private function registerCommand($name)
    {
        $bind_name = strtolower($name);
        $this->app->singleton("command.schema.$bind_name", function ($app) use ($name) {
            return $app->make('DevelMe\Console\\'.$name.'Command');
        });
    }
    
}
