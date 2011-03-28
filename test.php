<?
class flaf{
    private $moduledir ="./modules/";
    public function loadModules()
    {
        echo("hello");
        if (is_dir($this->moduledir)) {
            if ($dh = opendir($this->moduledir)) {
                while (($file = readdir($dh)) !== false) {
                    //include_once $this->moduledir . $file;
                    if(preg_match ("/([\S]+).class.php$/i", $file, $match))
                    {
                        $str = file_get_contents($this->moduledir . $file);
                        echo($this->moduledir . $file ."\n");

                        $flaf = eval($str);
                        var_dump($flaf);
                        $$match[1] = new $match[1]();
                        if(is_a($$match[1],"absModules"))
                        {
                            echo("yes\n");
                        }

                    }
                }
                closedir($dh);
            }
        }
    }

}

$flaf = new flaf();

$flaf->loadModules();
?>