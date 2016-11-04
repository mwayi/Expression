<?php 

namespace Smrtr\Expression\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command 
{
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

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['i', null, InputOption::VALUE_OPTIONAL, 'Inputs', null]
        ];
    }
}