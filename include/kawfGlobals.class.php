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
    private $forum = null;
    private bool $isLoaded = false;

    private array $indexes = [];
    private array $tthreads = [];
    private array $tthreads_by_tid = [];

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

    private function loadForumData(array $forum): bool {
        if (!$forum) {
            return false;
        }

        if (isset($forum['version']) && $forum['version'] == 1) {
            throw new Exception("Forum is under maintenance");
        }

        $this->forum = $forum;

        // Process options first
        $options = explode(",", $this->forum['options']);
        foreach ($options as $value) {
            $this->forum['option'][$value] = true;
        }

        // Load indexes
        $this->indexes = build_indexes($this->forum['fid']);

        // Load raw tracking data
        list($this->tthreads, $this->tthreads_by_tid) = load_tracking($this->forum['fid']);

        // Now we can safely set isLoaded
        $this->isLoaded = true;

        return true;
    }

    private function loadForum(string $shortname): bool {
        $sql = "select * from f_forums where shortname = ?";
        $forum = db_query_first($sql, array($shortname));
        return $this->loadForumData($forum);
    }

    private function loadForumById(int $fid): bool {
        $sql = "select * from f_forums where fid = ?";
        $forum = db_query_first($sql, array($fid));
        return $this->loadForumData($forum);
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

    public function getTThreads(): array {
        if (!$this->isLoaded) {
            throw new Exception("Forum not loaded");
        }
        return $this->tthreads;
    }

    public function getTThreadsByTid(): array {
        if (!$this->isLoaded) {
            throw new Exception("Forum not loaded");
        }
        return $this->tthreads_by_tid;
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

final class kawfGlobals { // `final` prevents inheritance
    private static $initialized = false;

    public static ?ServerInfo $server;
    public static ?ForumInfo $forum;

    public static function initialize(): void {
        if (self::$initialized) return;
        self::$initialized = true;

        self::$server = new ServerInfo();
        self::$forum = new ForumInfo(); // Initialize empty, will be loaded later
    }

    public static function loadForum(string $shortname): bool {
        try {
            self::$forum = ForumInfo::fromShortname($shortname);
            return self::$forum !== null;
        } catch (Exception $e) {
            error_log("Error loading forum: " . $e->getMessage());
            return false;
        }
    }

    public static function loadForumById(int $fid): bool {
        try {
            self::$forum = ForumInfo::fromFid($fid);
            return self::$forum !== null;
        } catch (Exception $e) {
            error_log("Error loading forum: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Private constructor to prevent instantiation of this static utility class.
     */
    private function __construct() {}
}

// Initialize kawfGlobals when the file is loaded
kawfGlobals::initialize();

function get_server(): ServerInfo {
    return kawfGlobals::$server;
}

function load_forum($shortname): bool
{
  return kawfGlobals::loadForum($shortname);
}

function get_forum(): array {
    if (!kawfGlobals::$forum || !kawfGlobals::$forum->isLoaded()) {
        throw new Exception("Forum not loaded");
    }
    return kawfGlobals::$forum->getForum();
}

// These should only be called by user/tracking.php, nowhere else.
// No other code should require multiple forums.
function set_forum(int $fid): array {
    kawfGlobals::loadForumById($fid);
    return get_forum();
}
function clear_forum() {
    kawfGlobals::$forum = null;
}

function get_forum_indexes(): array {
    if (!kawfGlobals::$forum || !kawfGlobals::$forum->isLoaded()) {
        throw new Exception("Forum not loaded");
    }
    return kawfGlobals::$forum->getIndexes();
}

function get_tthreads(): array {
    if (!kawfGlobals::$forum || !kawfGlobals::$forum->isLoaded()) {
        throw new Exception("Forum not loaded");
    }
    return kawfGlobals::$forum->getTThreads();
}

function get_tthreads_by_tid(): array {
    if (!kawfGlobals::$forum || !kawfGlobals::$forum->isLoaded()) {
        throw new Exception("Forum not loaded");
    }
    return kawfGlobals::$forum->getTThreadsByTid();
}

function has_forum_context(): bool {
    return kawfGlobals::$forum !== null && kawfGlobals::$forum->isLoaded();
}

// vim: ts=8 sw=4 et:
?>
