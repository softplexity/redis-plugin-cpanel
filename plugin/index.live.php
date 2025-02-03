<?php
require_once "/usr/local/cpanel/php/cpanel.php";
require_once __DIR__ . "/RedisManager.php";

$cpanel = new CPANEL();
$redisManager = new RedisManager($cpanel);

try {
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING) ?? 'status';
    $username = $redisManager->username;
    $userdetails = $redisManager->userdetails;

    if ($action === 'start') {
        $redisManager->startRedis();
        header("Location: index.live.php");
        exit;
    } elseif ($action === 'stop') {
        $redisManager->stopRedis();
        header("Location: index.live.php");
        exit;
    }

    // Generate Redis status details
    ob_start();
    $redisManager->checkRedisStatus();
    $status_output = ob_get_clean();

    $status_info = parseRedisStatus($status_output);

    echo generateHeader($cpanel);
    echo generateBodyContent($status_info);
    echo $cpanel->footer();
    $cpanel->end();
} catch (Exception $e) {
    echo 'Error: ' . htmlspecialchars($e->getMessage());
}

/**
 * Parses the output of the Redis status into an array of details.
 */
function parseRedisStatus($status_output)
{
    $status_info = explode(' ', $status_output);

    if (count($status_info) >= 5 && strtolower($status_info[0]) === 'running') {
        return [
            'status' => 'Running',
            'port' => $status_info[1],
            'password' => $status_info[2],
            'ip' => '127.0.0.1',
            'user' => 'root',
            'max_memory' => $status_info[3],
            'max_databases' => $status_info[4],
            'uninitiated' => 'N/A',
        ];
    } elseif (count($status_info) >= 1 && strtolower($status_info[0]) === 'uninitiated') {
        return [
            'status' => 'Not Running (Never Started)',
            'port' => 'N/A',
            'password' => 'N/A',
            'ip' => '127.0.0.1',
            'user' => 'root',
            'max_memory' => 'N/A',
            'max_databases' => 'N/A',
            'uninitiated' => $status_info[0],
        ];
    } else {
        return [
            'status' => 'Not Running (Stopped)',
            'port' => 'N/A',
            'password' => 'N/A',
            'ip' => '127.0.0.1',
            'user' => 'root',
            'max_memory' => 'N/A',
            'max_databases' => 'N/A',
            'uninitiated' => 'N/A',
        ];
    }
}

/**
 * Generates the header section with necessary styles.
 */
function generateHeader($cpanel)
{
    $stylesheets = '<link rel="stylesheet" href="redis_style.css" charset="utf-8"/>';
    return str_replace('</head>', $stylesheets . '</head>', $cpanel->header("Redis Manager"));
}

/**
 * Generates the main body content of the page.
 */
function generateBodyContent($status_info)
{
    ob_start();
    ?>
    <div class="body-content">
        <hr>
        <br>
        <p>
            <strong><a href="https://redis.io/">Redis</a></strong> is an open source, in-memory data
            store used for caching, as a vector database, streaming, and more.
        </p>
        <br>
        <pre>
            <?php if ($status_info['status'] === 'Running') : ?>
                Running - Port: <?= $status_info['port'] ?> | Password: <?= $status_info['password'] ?> | User: <?= $status_info['user'] ?>
            <?php endif; ?>
        </pre>
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="header-section">
                    <img src="./redis_icon.webp" alt="Redis" width="50" />
                    <h4>Redis Configuration:</h4>
                </div>
                <div class="status-section">
                    <?= generateStatusDetails($status_info) ?>
                </div>
                <hr>
                <form method="get" class="form-inline">
                    <input type="hidden" name="action" value="<?= $status_info['status'] === 'Running' ? 'stop' : 'start' ?>">
                    <button class="btn <?= $status_info['status'] === 'Running' ? 'btn-danger' : 'btn-success' ?>" type="submit">
                        <?= $status_info['status'] === 'Running' ? 'Stop Redis' : 'Start Redis' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Generates the HTML for redis status based on current status.
 */
function generateStatusDetails($status_info)
{
    if ($status_info['status'] === 'Running') {
        return "
            <p><strong>Status:</strong> <span class='status-label running'>{$status_info['status']}</span></p>
            <p><strong>IP:</strong> {$status_info['ip']}</p>
            <p><strong>Port:</strong> {$status_info['port']}</p>
            <p><strong>Password:</strong> {$status_info['password']}</p>
            <p><strong>Maximum Memory:</strong> {$status_info['max_memory']}</p>
            <p><strong>Maximum Databases:</strong> {$status_info['max_databases']}</p>
        ";
    } elseif ($status_info['status'] === 'Not Running (Never Started)') {
        return "
            <p><strong>Status:</strong> <span class='status-label not-running'>{$status_info['status']}</span></p>
            <p><strong>Message:</strong> Please click on the button below to <strong>Start Redis</strong>. It may take a few minutes for the first time.</p>
        ";
    } else {
        return "
            <p><strong>Status:</strong> <span class='status-label stopped'>Not Running</span></p>
        ";
    }
}
?>