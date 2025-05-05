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

final class ForumInfo {
    private $forum;
    private array $indexes = [];
    private bool $isLoaded = false;

    public function __construct() {
        $this->forum;
        $this->indexes = [];
    }

    public static function fromShortname(string $shortname): ?self {
        $instance = new self();
        if (!$instance->loadForum($shortname)) {
            return null;
        }
        return $instance;
    }

    public static function fromFid(int $fid): ?self {
        $instance = new self();
        if (!$instance->loadForumById($fid)) {
            return null;
        }
        return $instance;
    }

    private function loadForum(string $shortname): bool {
        $sql = "select * from f_forums where shortname = ?";
        $this->forum = db_query_first($sql, array($shortname));

        if (!$this->forum) {
            return false;
        }

        if (isset($this->forum['version']) && $this->forum['version'] == 1) {
            throw new Exception("Forum is under maintenance");
        }

        $this->indexes = $this->buildIndexes($this->forum['fid']);

        // Process options
        $options = explode(",", $this->forum['options']);
        foreach ($options as $value) {
            $this->forum['option'][$value] = true;
        }

        $this->isLoaded = true;
        return true;
    }

    private function loadForumById(int $fid): bool {
        $sql = "select * from f_forums where fid = ?";
        $this->forum = db_query_first($sql, array($fid));

        if (!$this->forum) {
            return false;
        }

        if (isset($this->forum['version']) && $this->forum['version'] == 1) {
            throw new Exception("Forum is under maintenance");
        }

        $this->indexes = $this->buildIndexes($fid);

        // Process options
        $options = explode(",", $this->forum['options']);
        foreach ($options as $value) {
            $this->forum['option'][$value] = true;
        }

        $this->isLoaded = true;
        return true;
    }

    private function buildIndexes(int $fid): array {
        $indexes = [];
        $sql = "select * from f_indexes where fid = ? and ( minmid != 0 or minmid < maxmid ) order by iid";
        $sth = db_query($sql, array($fid));
        while ($index = $sth->fetch()) {
            $indexes[] = $index;
        }
        $sth->closeCursor();
        return $indexes;
    }

    public function getForum(): array {
        if (!$this->isLoaded) {
            throw new Exception("Forum not loaded");
        }
        return $this->forum;
    }

    public function getIndexes(): array {
        if (!$this->isLoaded) {
            throw new Exception("Forum not loaded");
        }
        return $this->indexes;
    }

    public function isLoaded(): bool {
        return $this->isLoaded;
    }

    public function __toString(): string {
        if (!$this->isLoaded) {
            return "Forum Info: Not loaded";
        }
        $ret = "Forum Info:\n";
        foreach ($this->forum as $key => $value) {
            $ret .= "$key = '$value'\n";
        }
        return $ret;
    }
}

final class ThreadInfo {
    private $thread;
    private array $tthreads = [];
    private array $tthreads_by_tid = [];

    public function __construct() {
        $this->thread = [];
        $this->tthreads = [];
        $this->tthreads_by_tid = [];
    }

    public function __toString(): string {
        $ret = "Thread Info:\n";
        foreach ($this->thread as $key => $value) {
            $ret .= "$key = '$value'\n";
        }
        return $ret;
    }
}

kawfGlobals::initialize();

final class kawfGlobals { // `final` prevents inheritance
    private static $initialized = false;

    public static ?ServerInfo $server;
    public static ?ForumInfo $forum;
    public static ?ThreadInfo $thread;

    public static function initialize(): void {
        if (self::$initialized) return;
        self::$initialized = true;

        self::$server = new ServerInfo();
        self::$forum = new ForumInfo(); // Initialize empty, will be loaded later
        self::$thread = new ThreadInfo();
    }

    public static function loadForum(string $shortname): bool {
        $forum = ForumInfo::fromShortname($shortname);
        if ($forum === null) {
            return false;
        }
        self::$forum = $forum;
        return true;
    }

    public static function loadForumById(int $fid): bool {
        $forum = ForumInfo::fromFid($fid);
        if ($forum === null) {
            return false;
        }
        self::$forum = $forum;
        return true;
    }

    /**
     * Private constructor to prevent instantiation of this static utility class.
     */
    private function __construct() {}
}

function get_server(): ServerInfo {
    return kawfGlobals::$server;
}

function get_forum(): array {
    if (!kawfGlobals::$forum || !kawfGlobals::$forum->isLoaded()) {
        throw new Exception("Forum not loaded");
    }
    return kawfGlobals::$forum->getForum();
}

// This should only be called by user/tracking.php, nowhere else.
// No other code should require multiple forums.
function set_forum(int $fid): array {
    kawfGlobals::loadForumById($fid);
    return get_forum();
}

function get_forum_indexes(): array {
    if (!kawfGlobals::$forum || !kawfGlobals::$forum->isLoaded()) {
        throw new Exception("Forum not loaded");
    }
    return kawfGlobals::$forum->getIndexes();
}

function get_current_thread(): ThreadInfo {
    return kawfGlobals::$thread;
}

function has_forum_context(): bool {
    return kawfGlobals::$forum !== null && kawfGlobals::$forum->isLoaded();
}

// vim: ts=4 sw=4 et:
?>
