<?php 

namespace BlitzPHP\Cli\Console;

use Ahc\Cli\Application;
use Ahc\Cli\Input\Command As AhcCommand;
use BlitzPHP\Exceptions\CLIException;
use BlitzPHP\Loader\Services;

/**
 * Classe abstraite pour le fonctionnement de la console
 */
final class Console extends Application
{
    /**
     * Defini si on doit suppriemer les information du header (nom/version du framework) ou pas
     *
     * @var boolean
     */
    private bool $suppress = false;


    public function __construct(bool $suppress = false)
    {
        parent::__construct('BlitzPHP', \BlitzPHP\Core\Application::VERSION);
        
        $this->logo($this->logos[array_rand($this->logos)]);

        $this->suppress = $suppress;
    }

    /**
     * Enregistre les commandes executables
     *
     * @return void
     */
    public function registerCommands()
    {
        $this->addCommand(\BlitzPHP\Cli\Commands\Server\Serve::class);
    }

    /**
     * Ajoute une commande à la console
     *
     * @return void
     */
    private function addCommand(string $commandName)
    {
        if (!class_exists($commandName)) {
            throw new CLIException("La classe `$commandName` n'existe pas");
        }
        
        $instance = Services::container()->make($commandName, [$this]);
        
        if (! ($instance instanceof Command)) {
            throw CLIException::invalidCommand($commandName);
        }

        $command = new AhcCommand(
            $instance->name,
            $instance->description,
            false,
            $this
        );

        // Defini le groupe auquel appartient la commande
        if (method_exists($command, 'setGroup')) {
            $command->setGroup($instance->group);
        }

        // Defini l'usage et la version de la commande
        $command->usage($instance->usage)->version($instance->version);

        // Enregistre les options de la commande
        foreach ($instance->options as $option => $value) {
            $value = (array) $value;

            $description = $value[0];
            if (!is_string($description)) {
                continue;
            }

            $default = $value[1] ?? null;
            $filter = $value[2] ?? null;
            if ($filter != null && !is_callable($filter)) {
                $filter = null;
            }

            $command->option($option, $description, $filter, $default);
        }

        // Enregistre les arguments de la commande
        foreach ($instance->arguments as $argument => $value) {
            $value = (array) $value;

            $description = $value[0];
            if (!is_string($description)) {
                continue;
            }
            
            $default = $value[1] ?? null;

            $command->argument($argument, $description, $default);
        }

        $console = $this;

        $command->action(function () use ($instance, $command, $console) {
            if (!$console->suppress) {
                $console->start($instance->service);
            }

            $result = $instance->execute($command->values(false));

            if (!$console->suppress) {
                $console->end();
            }

            return $result;
        });

        $this->add($command, $instance->alias, false);
    }


    /**
     * Message d'entete commun a tous les services de la console
     *
     * @return void
     */
    public function start(string $service)
    {
        $io = $this->io();

        $eq_str = str_repeat('=', strlen($service));

        $io->write("==================================" . $eq_str,  true);
        $io->write("BlitzPHP Command Line Interface | " . $service, true);
        $io->write("==================================" . $eq_str,  true);
        $io->eol();
    }

    /**
     * Message de pied commun a tous les services de la console
     *
     * @return void
     */
    public function end()
    {
        $io = $this->io();
        
        $info = 'BlitzPHP v' . $this->version() . ' * klinge v1.0 * ' . date('Y-m-d H:i:s');
       
        $io->write("\n\n".str_repeat('-', strlen($info))."\n");
        $io->writer()->bold->info($info, true);
    }


    private $logos = [
        '
        /$$$$$$$  /$$ /$$   /$$              /$$$$$$$  /$$   /$$ /$$$$$$$ 
        | $$__  $$| $$|__/  | $$             | $$__  $$| $$  | $$| $$__  $$
        | $$  \ $$| $$ /$$ /$$$$$$  /$$$$$$$$| $$  \ $$| $$  | $$| $$  \ $$
        | $$$$$$$ | $$| $$|_  $$_/ |____ /$$/| $$$$$$$/| $$$$$$$$| $$$$$$$/
        | $$__  $$| $$| $$  | $$      /$$$$/ | $$____/ | $$__  $$| $$____/ 
        | $$  \ $$| $$| $$  | $$ /$$ /$$__/  | $$      | $$  | $$| $$      
        | $$$$$$$/| $$| $$  |  $$$$//$$$$$$$$| $$      | $$  | $$| $$      
        |_______/ |__/|__/   \___/ |________/|__/      |__/  |__/|__/              
        ',
        "
        ____  _ _ _       _____  _    _ _____  
        |  _ \| (_) |     |  __ \| |  | |  __ \ 
        | |_) | |_| |_ ___| |__) | |__| | |__) |
        |  _ <| | | __|_  /  ___/|  __  |  ___/ 
        | |_) | | | |_ / /| |    | |  | | |     
        |____/|_|_|\__/___|_|    |_|  |_|_|            
        ",
        "
        ______ _ _ _      ______ _   _ ______ 
        | ___ \ (_) |     | ___ \ | | || ___ \
        | |_/ / |_| |_ ___| |_/ / |_| || |_/ /
        | ___ \ | | __|_  /  __/|  _  ||  __/ 
        | |_/ / | | |_ / /| |   | | | || |    
        \____/|_|_|\__/___\_|   \_| |_/\_|            
        ",
        "
        ____  __    __  ____  ____  ____  _  _  ____ 
        (  _ \(  )  (  )(_  _)(__  )(  _ \/ )( \(  _ \
         ) _ (/ (_/\ )(   )(   / _/  ) __/) __ ( ) __/
        (____/\____/(__) (__) (____)(__)  \_)(_/(__)  
        ",
        "
        .----. .-.   .-. .---.  .---. .----. .-. .-..----. 
        | {}  }| |   | |{_   _}{_   / | {}  }| {_} || {}  }
        | {}  }| `--.| |  | |   /    }| .--' | { } || .--' 
        `----' `----'`-'  `-'   `---' `-'    `-' `-'`-'       
        ",
        '
        _______   ___        __  ___________  ________     _______    __    __    _______   
        |   _  "\ |"  |      |" \("     _   ")("      "\   |   __ "\  /" |  | "\  |   __ "\  
        (. |_)  :)||  |      ||  |)__/  \\__/  \___/   :)  (. |__) :)(:  (__)  :) (. |__) :) 
        |:     \/ |:  |      |:  |   \\_ /       /  ___/   |:  ____/  \/      \/  |:  ____/  
        (|  _  \\  \  |___   |.  |   |.  |      //  \__    (|  /      //  __  \\  (|  /      
        |: |_)  :)( \_|:  \  /\  |\  \:  |     (:   / "\  /|__/ \    (:  (  )  :)/|__/ \     
        (_______/  \_______)(__\_|_)  \__|      \_______)(_______)    \__|  |__/(_______)    
                                                                                             
        ',
        "            
        ,-----.  ,--.,--.  ,--.         ,------. ,--.  ,--.,------.  
        |  |) /_ |  |`--',-'  '-.,-----.|  .--. '|  '--'  ||  .--. ' 
        |  .-.  \|  |,--.'-.  .-'`-.  / |  '--' ||  .--.  ||  '--' | 
        |  '--' /|  ||  |  |  |   /  `-.|  | --' |  |  |  ||  | --'  
        `------' `--'`--'  `--'  `-----'`--'     `--'  `--'`--'      
        ",
        "
        ____  _ _ _       ____  _   _ ____  
        | __ )| (_) |_ ___|  _ \| | | |  _ \ 
        |  _ \| | | __|_  / |_) | |_| | |_) |
        | |_) | | | |_ / /|  __/|  _  |  __/ 
        |____/|_|_|\__/___|_|   |_| |_|_|           
        ",
        "
        ______   __    _   _          _______  ____  ____  _______   
        |_   _ \ [  |  (_) / |_       |_   __ \|_   ||   _||_   __ \  
          | |_) | | |  __ `| |-'____    | |__) | | |__| |    | |__) | 
          |  __'. | | [  | | | [_   ]   |  ___/  |  __  |    |  ___/  
         _| |__) || |  | | | |, .' /_  _| |_    _| |  | |_  _| |_     
        |_______/[___][___]\__/[_____]|_____|  |____||____||_____|    
                                                                      
        ",
        "
        ██████╗ ██╗     ██╗████████╗███████╗██████╗ ██╗  ██╗██████╗ 
        ██╔══██╗██║     ██║╚══██╔══╝╚══███╔╝██╔══██╗██║  ██║██╔══██╗
        ██████╔╝██║     ██║   ██║     ███╔╝ ██████╔╝███████║██████╔╝
        ██╔══██╗██║     ██║   ██║    ███╔╝  ██╔═══╝ ██╔══██║██╔═══╝ 
        ██████╔╝███████╗██║   ██║   ███████╗██║     ██║  ██║██║     
        ╚═════╝ ╚══════╝╚═╝   ╚═╝   ╚══════╝╚═╝     ╚═╝  ╚═╝╚═╝             
        "
    ];
}
