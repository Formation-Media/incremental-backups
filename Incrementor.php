<?php
namespace Formation\Incrementor;

use Throwable;
use ZipArchive;

class Incrementor{
    
    private $dir;
    private $incremental;
    private $skip;
    private $target;

    public function __construct($dir,string $target='./',bool $incremental=true,array $skip=array())
    {
        if(!is_dir($target)){
            mkdir($target,0755,true);
        }
        $skip[]=$target;
        $this->dir        =$dir;
        $this->incremental=$incremental;
        $this->target     =$target.'/'.date('Y-m-d_H-i-s').'.zip';
        $this->skip       =$skip;
    }
    public function run()
    {
        if(is_dir($this->dir)){
            $meta     =array();
            $meta_file=str_replace('/','-',substr($this->dir,1)).'.json';
            $to_backup=array();
            if(is_file($meta_file) && $this->incremental){
                $meta=json_decode(file_get_contents($meta_file),true);
            }
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->dir)) as $fileInfo) {
                if($fileInfo->isFile()){
                    $path=str_replace($this->dir.'/','',$fileInfo->getRealPath());
                    if(!array_key_exists($path,$meta)){
                        $meta[$path]=filemtime($fileInfo->getRealPath());
                        $to_backup[]=$path;
                    }
                }
            }
            if($to_backup){
                $archive=new ZipArchive();
                if ($archive->open($this->target, ZipArchive::CREATE)!==TRUE) {
                    exit("cannot open <$this->target>\n");
                }
                foreach($to_backup as $file){
                    try{
                        $archive->addFile($file,);
                    }catch(Throwable $t){
                        print_r($t->getMessage());
                    }
                }
                $archive->close();
            }
        }
    }
}
