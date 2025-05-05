<?php

final class ServerInfo {
    public string $name = '';
    public string $port = '';
    public string $remoteAddr = '';
    public string $httpHost = '';
    public string $requestUri = '';
    public string $requestPath = '';
    public string $scriptName = '';
    public string $pathInfo = '';
    public string $queryString = '';
    public string $serverSoftware = '';

    public function __construct() {
        $this->name = $_SERVER['SERVER_NAME'] ?? '';
        $this->port = $_SERVER['SERVER_PORT'] ?? '';
        $this->remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        $this->httpHost = $_SERVER['HTTP_HOST'] ?? '';
        $this->requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $this->requestPath = (parse_url($_SERVER['REQUEST_URI'] ?? ''))['path'] ?? '';
        $this->scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $this->pathInfo = $_SERVER['PATH_INFO'] ?? '';
        $this->queryString = $_SERVER['QUERY_STRING'] ?? '';
        $this->serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '';
    }

    public function __toString(): string {
        $ret = "url = '" . full_url($_SERVER) . "'\n";
        foreach ($this as $key => $value) {
            $ret .= strtolower($key) . " = '" . $value . "'\n";
        }
        return $ret;
    }
}

kawfGlobals::initialize();

final class kawfGlobals { // `final` prevents inheritance
    private static $initialized = false;

    //public static $someVar;
    public static ?ServerInfo $server;

    public static function initialize(): void {
        if (self::$initialized) return;
        self::$initialized = true;

        //self::$someVar = false;

        self::$server = new ServerInfo();
    }

    /**
     * Private constructor to prevent instantiation of this static utility class.
     */
    private function __construct() {}
}

function get_server(): ServerInfo {
    return kawfGlobals::$server;
}
// vim: ts=4 sw=4 et:
?>
