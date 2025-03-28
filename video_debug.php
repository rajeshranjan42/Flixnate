<?php
require_once 'includes/init.php';
require_once 'includes/db_connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to log errors
function logError($message) {
    error_log("Video Debug: " . $message);
    // Also output to browser for debugging
    echo "<div style='color:red;'>" . htmlspecialchars($message) . "</div>";
}

// Get content ID from URL
$content_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
logError("Checking content ID: " . $content_id);

if (!$content_id) {
    logError("No content ID provided");
    echo "<p>Please provide a content ID in the URL (e.g., video_debug.php?id=1)</p>";
    // List available content IDs
    $result = mysqli_query($conn, "SELECT id, title FROM content WHERE video_path IS NOT NULL OR video_url IS NOT NULL");
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<h3>Available content:</h3><ul>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<li><a href='video_debug.php?id=" . $row['id'] . "'>" . htmlspecialchars($row['title']) . " (ID: " . $row['id'] . ")</a></li>";
        }
        echo "</ul>";
    }
    exit;
}

// Get content details
$stmt = mysqli_prepare($conn, "SELECT * FROM content WHERE id = ?");
if (!$stmt) {
    logError("Failed to prepare content query: " . mysqli_error($conn));
    die("Database error occurred");
}

mysqli_stmt_bind_param($stmt, "i", $content_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    logError("Content not found for ID: " . $content_id);
    die("Content not found");
}

$content = mysqli_fetch_assoc($result);
logError("Content found: " . $content['title']);

// Get video source (handle both video_path and video_url)
$video_source = '';
$video_type = 'video/mp4'; // default video type

if (!empty($content['video_url'])) {
    $video_source = $content['video_url'];
    logError("Using video URL: " . $video_source);
} elseif (!empty($content['video_path'])) {
    // Handle both full paths and relative paths
    if (strpos($content['video_path'], 'http') === 0) {
        $video_source = $content['video_path'];
    } else {
        $video_source = strpos($content['video_path'], 'uploads/') === 0 
            ? $content['video_path'] 
            : 'uploads/videos/' . basename($content['video_path']);
    }
    logError("Using video path: " . $video_source);
}

// Verify video exists if it's a local file
$video_exists = false;
$error_message = '';

if (!empty($video_source)) {
    if (strpos($video_source, 'http') === 0) {
        $video_exists = true;
        logError("External URL detected: " . $video_source);
    } else {
        $absolute_path = realpath($video_source);
        logError("Checking local file:");
        logError("Relative path: " . $video_source);
        logError("Absolute path: " . ($absolute_path ?: 'Not found'));
        
        if (!file_exists($video_source)) {
            $error_message = "Video file does not exist";
            logError("Error: " . $error_message);
        } elseif (!is_readable($video_source)) {
            $error_message = "Video file is not readable";
            logError("Error: " . $error_message);
        } else {
            $video_exists = true;
            logError("File exists and is readable");
            logError("File size: " . filesize($video_source) . " bytes");
            logError("File permissions: " . decoct(fileperms($video_source)));
            
            // Determine video type based on file extension
            $extension = strtolower(pathinfo($video_source, PATHINFO_EXTENSION));
            switch ($extension) {
                case 'webm':
                    $video_type = 'video/webm';
                    break;
                case 'mp4':
                    $video_type = 'video/mp4';
                    break;
                case 'ogg':
                case 'ogv':
                    $video_type = 'video/ogg';
                    break;
            }
        }
    }
} else {
    $error_message = "No video source specified";
    logError("Error: " . $error_message);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Debug - <?php echo htmlspecialchars($content['title']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background: #f5f5f5; }
        h1, h2 { color: #333; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .video-container { margin: 20px 0; }
        .debug-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .debug-info h3 { margin-top: 0; }
        .debug-info pre { background: #eee; padding: 10px; overflow: auto; }
        .test-section { margin: 30px 0; padding: 20px; background: #e9f7fe; border-radius: 5px; }
        video { max-width: 100%; background: #000; }
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .test-controls { margin-top: 15px; padding: 10px; background: #f0f8ff; border-radius: 5px; }
        .test-controls button { padding: 5px 10px; background: #4CAF50; color: white; border: none; border-radius: 3px; cursor: pointer; }
        .test-controls button:hover { background: #45a049; }
        #diagnostic-results { font-size: 14px; line-height: 1.4; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Video Debug Tool</h1>
        <p><a href="watch.php?id=<?php echo $content_id; ?>">Go to normal watch page</a> | <a href="video_debug.php">Back to list</a></p>
        
        <div class="debug-info">
            <h3>Content Information</h3>
            <p><strong>Title:</strong> <?php echo htmlspecialchars($content['title']); ?></p>
            <p><strong>ID:</strong> <?php echo $content_id; ?></p>
            <p><strong>Video Path in DB:</strong> <?php echo htmlspecialchars($content['video_path'] ?: 'Not set'); ?></p>
            <p><strong>Video URL in DB:</strong> <?php echo htmlspecialchars($content['video_url'] ?: 'Not set'); ?></p>
            <p><strong>Resolved Source:</strong> <?php echo htmlspecialchars($video_source); ?></p>
            <p><strong>Video Type:</strong> <?php echo $video_type; ?></p>
            <p><strong>File Exists:</strong> <?php echo $video_exists ? '<span class="success">Yes</span>' : '<span class="error">No</span>'; ?></p>
            <?php if (!empty($error_message)): ?>
                <p><strong>Error:</strong> <span class="error"><?php echo htmlspecialchars($error_message); ?></span></p>
            <?php endif; ?>
        </div>

        <?php if ($video_exists): ?>
            <div class="test-section">
                <h2>Test 1: Direct Video Tag (No Plyr)</h2>
                <p>Testing native HTML5 video player without any JavaScript libraries:</p>
                <div class="video-container">
                    <video controls width="100%" id="native-player" crossorigin="anonymous">
                        <source src="<?php echo htmlspecialchars($video_source); ?>" type="<?php echo $video_type; ?>">
                        Your browser does not support the video tag.
                    </video>
                </div>
                <div id="native-player-log"></div>
            </div>

            <div class="test-section">
                <h2>Test 2: Stream.php Source</h2>
                <p>Testing with stream.php as the source:</p>
                <div class="video-container">
                    <video controls width="100%" id="stream-player" crossorigin="anonymous">
                        <source src="stream.php?id=<?php echo $content_id; ?>" type="<?php echo $video_type; ?>">
                        Your browser does not support the video tag.
                    </video>
                </div>
                <div id="stream-player-log"></div>
            </div>

            <div class="test-section">
                <h2>Test 3: With Plyr Library</h2>
                <p>Testing with Plyr library (same as watch.php):</p>
                <div class="video-container">
                    <video id="plyr-player" playsinline controls crossorigin="anonymous">
                        <source src="stream.php?id=<?php echo $content_id; ?>" type="<?php echo $video_type; ?>">
                        Your browser does not support the video tag.
                    </video>
                </div>
                <div id="plyr-player-log"></div>
            </div>
        <?php else: ?>
            <div class="error-message">
                <h3><i class="fas fa-exclamation-circle"></i> Video Not Available</h3>
                <p>Sorry, this video is currently unavailable. <?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($video_exists): ?>
    <script src="https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.css">
    <script>
        // Helper function to log video events
        function logVideoEvent(elementId, message) {
            const logElement = document.getElementById(elementId);
            const timestamp = new Date().toLocaleTimeString();
            logElement.innerHTML += `<div>[${timestamp}] ${message}</div>`;
        }

        // Check if browser supports the video format
        function checkVideoSupport(mimeType) {
            const video = document.createElement('video');
            return video.canPlayType(mimeType) !== '';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Check video format support
            const videoType = '<?php echo $video_type; ?>';
            const isSupported = checkVideoSupport(videoType);
            console.log(`Browser ${isSupported ? 'supports' : 'does NOT support'} video type: ${videoType}`);
            
            if (!isSupported) {
                document.querySelectorAll('.video-container').forEach(container => {
                    container.insertAdjacentHTML('beforeend', 
                        `<div class="error">Warning: Your browser may not support ${videoType} format</div>`);
                });
            }
            // Test 1: Native video player
            const nativePlayer = document.getElementById('native-player');
            if (nativePlayer) {
                nativePlayer.addEventListener('loadstart', () => {
                    logVideoEvent('native-player-log', 'Native player: Loading started');
                });
                nativePlayer.addEventListener('loadedmetadata', () => {
                    logVideoEvent('native-player-log', `Native player: Metadata loaded. Duration: ${nativePlayer.duration}s, Size: ${nativePlayer.videoWidth}x${nativePlayer.videoHeight}`);
                });
                nativePlayer.addEventListener('canplay', () => {
                    logVideoEvent('native-player-log', 'Native player: Can start playing');
                });
                nativePlayer.addEventListener('error', (e) => {
                    const errorMessage = nativePlayer.error ? nativePlayer.error.message : 'Unknown error';
                    logVideoEvent('native-player-log', `Native player ERROR: ${errorMessage}`);
                    console.error('Native player error:', errorMessage, nativePlayer.error);
                });
            }

            // Test 2: Stream.php source
            const streamPlayer = document.getElementById('stream-player');
            if (streamPlayer) {
                streamPlayer.addEventListener('loadstart', () => {
                    logVideoEvent('stream-player-log', 'Stream player: Loading started');
                });
                streamPlayer.addEventListener('loadedmetadata', () => {
                    logVideoEvent('stream-player-log', `Stream player: Metadata loaded. Duration: ${streamPlayer.duration}s, Size: ${streamPlayer.videoWidth}x${streamPlayer.videoHeight}`);
                });
                streamPlayer.addEventListener('canplay', () => {
                    logVideoEvent('stream-player-log', 'Stream player: Can start playing');
                });
                streamPlayer.addEventListener('error', (e) => {
                    const errorMessage = streamPlayer.error ? streamPlayer.error.message : 'Unknown error';
                    logVideoEvent('stream-player-log', `Stream player ERROR: ${errorMessage}`);
                    console.error('Stream player error:', errorMessage, streamPlayer.error);
                    
                    // Check network status
                    const videoSource = streamPlayer.querySelector('source');
                    if (videoSource) {
                        console.log('Attempting to fetch video source:', videoSource.src);
                        fetch(videoSource.src, { method: 'HEAD' })
                            .then(response => {
                                console.log('Video source response:', response.status, response.statusText);
                            })
                            .catch(err => {
                                console.error('Failed to fetch video source:', err);
                            });
                    }
                });
            }

            // Test 3: Plyr player
            const plyrElement = document.getElementById('plyr-player');
            if (plyrElement) {
                plyrElement.addEventListener('error', (e) => {
                    const errorMessage = plyrElement.error ? plyrElement.error.message : 'Unknown error';
                    logVideoEvent('plyr-player-log', `Video element ERROR: ${errorMessage}`);
                    console.error('Plyr element error:', errorMessage, plyrElement.error);
                });

                const player = new Plyr(plyrElement, {
                    debug: true,
                    controls: [
                        'play-large',
                        'play',
                        'progress',
                        'current-time',
                        'mute',
                        'volume',
                        'fullscreen'
                    ],
                    loadSprite: false,
                    iconUrl: 'https://cdn.plyr.io/3.7.8/plyr.svg',
                    blankVideo: 'https://cdn.plyr.io/static/blank.mp4'
                });

                player.on('ready', () => {
                    logVideoEvent('plyr-player-log', 'Plyr: Player is ready');
                });

                player.on('loadeddata', () => {
                    logVideoEvent('plyr-player-log', 'Plyr: Video data loaded');
                });

                player.on('error', (error) => {
                    logVideoEvent('plyr-player-log', `Plyr ERROR: ${JSON.stringify(error)}`);
                    console.error('Plyr player error:', error);
                });
                
                // Add diagnostic button to test video source
                const diagnosticDiv = document.createElement('div');
                diagnosticDiv.className = 'test-controls';
                diagnosticDiv.innerHTML = `
                    <button id="test-video-btn" class="btn btn-primary">Run Video Diagnostics</button>
                    <div id="diagnostic-results" style="margin-top: 10px;"></div>
                `;
                document.getElementById('plyr-player-log').after(diagnosticDiv);
                
                document.getElementById('test-video-btn').addEventListener('click', function() {
                    const resultDiv = document.getElementById('diagnostic-results');
                    resultDiv.innerHTML = '<div>Running diagnostics...</div>';
                    
                    const videoSource = plyrElement.querySelector('source').src;
                    console.log('Testing video source:', videoSource);
                    
                    // Test if video source is accessible
                    fetch(videoSource, { method: 'HEAD' })
                        .then(response => {
                            if (response.ok) {
                                resultDiv.innerHTML += `<div class="success">✓ Video source is accessible (${response.status})</div>`;
                                return fetch(videoSource, { method: 'GET', headers: { Range: 'bytes=0-1024' } });
                            } else {
                                resultDiv.innerHTML += `<div class="error">✗ Video source returned status: ${response.status}</div>`;
                                throw new Error(`HTTP error ${response.status}`);
                            }
                        })
                        .then(response => {
                            if (response.headers.get('Content-Type')) {
                                resultDiv.innerHTML += `<div class="success">✓ Content-Type: ${response.headers.get('Content-Type')}</div>`;
                            } else {
                                resultDiv.innerHTML += `<div class="error">✗ No Content-Type header returned</div>`;
                            }
                            
                            if (response.headers.get('Accept-Ranges')) {
                                resultDiv.innerHTML += `<div class="success">✓ Server supports range requests</div>`;
                            } else {
                                resultDiv.innerHTML += `<div class="warning">! Server may not support range requests</div>`;
                            }
                            
                            resultDiv.innerHTML += `<div>Complete! Check browser console for more details.</div>`;
                        })
                        .catch(err => {
                            resultDiv.innerHTML += `<div class="error">✗ Error: ${err.message}</div>`;
                            console.error('Diagnostic error:', err);
                        });
                });
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>