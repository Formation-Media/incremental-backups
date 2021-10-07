<?php
namespace Formation\Incrementor;

use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

class Incrementor{

    private $dir;
    private $is_incremental;
    private $is_laravel;
    private $skips;
    private $target;

    public function __construct($dir,string $target='./',bool $is_incremental=true,array $skips=array())
    {
        $this->is_laravel=defined('LARAVEL_START');
        if($this->is_laravel){
            if(!Storage::exists($target)){
                Storage::makeDirectory($target);
            }
        }else{
            if(!is_dir(ROOT.$target)){
                mkdir(ROOT.$target,0755,true);
            }
        }
        $skips[]=$target;
        if($this->is_laravel){
            $skips[]='storage/framework';
        }
        $this->dir           =$dir;
        $this->is_incremental=$is_incremental;
        $this->target        =$target.'/'.date('Y-m-d_H-i-s').($is_incremental && $meta?'-incremental':'').'.zip';
        $this->skips         =$skips;
    }
    public function run()
    {
        if(is_dir($this->dir)){
            $meta     =array();
            $meta_file=dirname($this->target).'/meta.json';
            $to_backup=array();
            $status = [];
            if(is_file($meta_file) && $this->is_incremental){
                $meta=json_decode(file_get_contents($meta_file),true);
            }
            $iterator = new \RecursiveDirectoryIterator($this->dir);
            $filter = new IteratorFilter($iterator, $this->skips);
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
            $meta=json_encode($meta, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
            if($this->is_laravel){
                Storage::put($meta_file,$meta);
            }else{
                file_put_contents(ROOT.$meta_file,$meta);
            }
            if($to_backup){
                $archive=new ZipArchive();
                if($this->is_laravel){
                    $status=$archive->open(Storage::path($this->target), ZipArchive::CREATE);
                }else{
                    $status=$archive->open(ROOT.$this->target, ZipArchive::CREATE);
                }
                if ($status!==TRUE) {
                    exit("cannot open <$this->target>\n");
                }
                foreach($to_backup as $file){
                    try{
                        if($this->is_laravel){
                            $archive->addFile(base_path($file),$file);
                        }else{
                            $archive->addFile($file,str_replace(ROOT,'',$file));
                        }
                    }catch(Throwable $t){
                        \Log::error($t->getMessage());
                    }
                }
                $archive->close();
            }
            return $status;
        }
    }
}
