<?php

namespace Ndexondeck\Lauditor\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Ndexondeck\Lauditor\Sql;

class DatabaseFlush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:flush {--truncate} {--keep-tasks} {--no-backup} {--backup-only} {--mode=} {--skip=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush database and backs up insert statement in storage/app';

    private $lBrace = '`';
    private $rBrace = '`';
    private $preStatement = '';
    private $postStatement = '';
    private $quotes = '"';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        //
        if(!is_dir('storage'))
            throw new \Exception("Please run from laravel root folder");

        //reorder by migration sequence
        $migrations = [];
        try{
            foreach (DB::table('migrations')->get() as $migration){
                if(!strstr($migration->migration,"create")) continue;
                $x = str_replace("create_","",str_replace("_table","",substr($migration->migration,18,strlen($migration->migration))));
                $migrations[$x] = $x;
            }
        }catch (\Exception $e){
            throw new \Exception("Nothing to flush as migration table doesn't exist");
        }

        $migrations = array_values($migrations);

        if($db_connections = config('ndexondeck.lauditor.connections') and !empty($db_connections)){
            foreach ($db_connections as $db_connection){
                $connections[] = (new Sql())->connection($db_connection);
            }
        }
        else $connections[] = new Sql();

        $options = $this->option();

        if($options['mode']){
            switch ($options['mode']){
                case "sqlsrv":
                    $this->lBrace = "[";$this->rBrace = "]";
                    $this->quotes = "'";
                    $this->preStatement = "SET IDENTITY_INSERT dbo.%s ON;";
                    $this->postStatement = "SET IDENTITY_INSERT dbo.%s OFF;";
                    break;
                default: break;
            }
        }


        $skippable = ($options['skip'])?explode(',',$options['skip']):[];

        /** @var Sql $connection */
        foreach($connections as $connection){

            $db = $connection->db();

            $tables = $connection->showTables();

            if(empty($tables)){
                echo("Database with connection ".$connection->getConnection()." is empty\n");
                continue;
            }

            if($options['truncate']){

                $db->statement($connection->disableChecks);

                foreach($tables as $table){
                    //Truncate
                    if($table == "migrations") continue;
                    if($options['keep-tasks'] and in_array($table,['tasks','modules'])) continue;
                    $db->statement("TRUNCATE TABLE $table");
                }

                $db->statement($connection->enableChecks);

                echo "->>Database tables has been truncated successfully...\n";

                continue;
            }

            $insert_sql = $connection->disableChecks."\n\n";
            $done = $skippable;
            $drop_tables = [];
            $table_name = "";

            foreach($migrations as $k=>$table){
                //Back up data to storage

                if(!in_array($table,$tables)) continue;

                $done[] =  $table;

                if(in_array($table,$skippable)) continue;

                echo "Backing up $table...\n";

                $sql_gotten = (!empty($this->preStatement))?sprintf($this->preStatement,$table)."\n":"";
                $sql_gotten .= $this->backup_table($db,$table);
                $sql_gotten .= (!empty($this->postStatement))?sprintf($this->postStatement,$table)."\n\n":"";

                if(trim($sql_gotten) != ""){
                    $table_name .= $table;
                    $insert_sql .= $sql_gotten;
                }

                $drop_tables[] = $table;
            }

            $pending = array_diff($tables,$done);
            foreach($pending as $table){
                //Back up data to storage

                echo "Backing up $table...\n";

                $sql_gotten = (!empty($this->preStatement))?sprintf($this->preStatement,$table)."\n":"";
                $sql_gotten .= $this->backup_table($db,$table);
                $sql_gotten .= (!empty($this->postStatement))?sprintf($this->postStatement,$table)."\n\n":"";

                if(trim($sql_gotten) != ""){
                    $table_name .= $table;
                    $insert_sql .= $sql_gotten;
                }
            }

            $insert_sql .= $connection->enableChecks."\n";

            if(!$options['no-backup']) {
                $path = "storage/app/";

                //save file
                $fn = 'db-backup-' . (md5($table_name)) . '.sql';
                echo "\n>> Creating sql file $fn in $path\n";
                if(file_exists($path . $fn)) unlink($path.$fn);

                $handle = fopen($path . $fn, 'w+');
                fwrite($handle, $insert_sql);
                fclose($handle);

                echo "->>Database back up complete...\n\n";
            }

            if($options['backup-only']) {
                echo "->>Only backup is done...\n\n";
                continue;
            }

            echo "->>Commencing database flush...\n\n";

            $db->statement($connection->killChecks);

            $drop_tables = array_reverse($drop_tables);

            foreach($pending as $k=>$drop_table){
                try{
                    $db->statement("drop table $drop_table");
                    unset($pending[$k]);
                }catch (\Exception $e){};
            }

            foreach($drop_tables as $drop_table){
                $db->statement("drop table $drop_table");
            }

            foreach($pending as $k=>$drop_table){
                try{
                    $db->statement("drop table $drop_table");
                    unset($pending[$k]);
                }catch (\Exception $e){};
            }

            $db->statement($connection->enableChecks);

            echo "->>DB flush complete, you can now run your migration...\n\n";
        }


    }

    private function backup_table($db,$table) {

        $limit = 100;
        $result = $db->table($table)->get();
        $columns = $db->getSchemaBuilder()->getColumnListing($table);
        $last_column = end($columns);
        $columns = $this->lBrace.implode($this->rBrace.",".$this->lBrace,$columns).$this->rBrace;

        $return = '';
        $more = true;

        $i = 0;
        while($more)
        {
            $sep = " ";
            $head = "INSERT INTO $table ($columns) VALUES";
            $tail = "";
            $more = false;

            while($i < count($result))
            {
                $row = $result[$i];
                $return.= $head;
                $return.= $sep.'(';

                foreach($row as $key=>$field)
                {
                    $field = is_null($field)?null:str_replace("\n","\\n",addslashes($field));
                    if (isset($field)) { $return.= $this->quotes.$field.$this->quotes ; } else { $return.= 'NULL'; }
                    if ($key != $last_column) { $return.= ','; }
                }
                $return.= ")";
                $more = true;
                $i++;
                $head="";$sep = ",";$tail=";\n";
                if($i == $limit){
                    $limit = $limit + 100;
                    break;
                }
            }
            $return.= $tail;
        }

        return $return;
    }

}