<?php
namespace Formation\Incrementor;

require('IteratorFilter.php');

use Throwable;
use ZipArchive;

class Incrementor{
    
    private $dir;
    private $incremental;
    private $skips;
    private $target;

    public function __construct($dir,string $target='./',bool $incremental=true,array $skips=array())
    {
        if(!is_dir($target)){
            mkdir($target,0755,true);
        }
        $skips[]='#'.$target.'#';
        $this->dir        =$dir;
        $this->incremental=$incremental;
        $this->target     =$target.'/'.date('Y-m-d_H-i-s').'.zip';
        $this->skips       =$skips;
        $this->directory = $target;
        $this->target_dir = $target;
    }
    public function run()
    {
        if(is_dir($this->dir)){
            $meta     =array();
            $meta_file=str_replace('/','-',substr($this->dir,1)).'.json';
            $to_backup=array();
            $status = [];
            if(is_file($meta_file) && $this->incremental){
                $meta=json_decode(file_get_contents($meta_file),true);
            }
            $iterator = new \RecursiveDirectoryIterator($this->dir);
            $filter = new \Formation\Incrementor\IteratorFilter($iterator, $this->skips);
            $filtered_iterator = new \RecursiveIteratorIterator($filter);
            foreach ($filtered_iterator as $fileInfo) {
                if($fileInfo->isFile()){
                    $path=str_replace($this->dir.'/','',$fileInfo->getRealPath());
                    if(!array_key_exists($path,$meta)){
                        $meta[$path]=filemtime($fileInfo->getRealPath());
                        $to_backup[]=$path;
                    } else if (filemtime($fileInfo->getRealPath()) > $meta[$path]) {
                        $meta[$path] = filemtime($fileInfo->getRealPath()); 
                        $to_backup[]=$path;
                    }
                }
            }
            if(!$this->incremental){
                $meta_json = json_encode($meta, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
                file_put_contents($meta_file, $meta_json);
            }
            if($to_backup){
                $archive=new ZipArchive();
                if ($archive->open($this->target, ZipArchive::CREATE)!==TRUE) {
                    exit("cannot open <$this->target>\n");
                }
                foreach($to_backup as $file){
                    try{
                        $result = $archive->addFile($file);
                        $status['result'][$to_backup] = $result;
                    }catch(Throwable $t){
                        print_r($t->getMessage());
                        $status['errors'][] = $t->getMessage();
                    }
                    $status['archive'][$to_backup] = $archive->getStatusString();
                }
                // file_put_contents(public_path($this->target_dir).'status.json', json_encode($status, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
                $archive->close();
            }
            return $status;
        }
    }
}
