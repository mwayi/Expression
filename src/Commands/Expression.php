<?php 

namespace Smrtr\Expression\Commands;

use Smrtr\Expression\Expression;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Expression extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'smrtr.expression';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Compile a smrtr expression';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$expression = new Expression($this->option('i'));
		$expressionObject = $expression->toArray();

		pre_dump($expressionObject);
	}

	/**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['i', null, InputOption::VALUE_OPTIONAL, 'Input', null]
        ];
    }

	/**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {	
    	$response = null;

    	try {
	        $response = $this->fire();
	    }
	    catch(\Exception $e) {
	    	return $this->error("\nException: Fatal Error.\n" . $e->getMessage());
	    }

	    return $response;
    }

}