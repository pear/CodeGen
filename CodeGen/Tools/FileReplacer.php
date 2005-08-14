<?php
class CodeGen_Tools_FileReplacer
{
    protected $fileName;
    protected $tempName;
    protected $fp;

    function __construct($fileName)
    {
        $this->fileName = $fileName;

        $this->tempName = tempnam(dirname($fileName), "~tmp");
        
        $this->fp = fopen($this->tempName, "w");
    }

    function puts($string)
    {
        return fputs($this->fp, $string);
    }

    function close()
    {
        fclose($this->fp);

        if ( !file_exists($this->fileName)
             || (@filesize($this->fileName) != @filesize($this->tempName))
             || (md5(@file_get_contents($this->fileName)) != md5(file_get_contents($this->tempName)))) {
            if (file_exists($this->fileName)) {
                @rename($this->fileName, $this->fileName.".bak");
            } 
            if (!@rename($this->tempName, $this->fileName)) {
                unlink($this->tempName);
                return PEAR::raiseError("Can't write output file '{$this->fileName}'");
            }
        } else {
            unlink($this->tempName);
        }     

        return true;
    }
}
