<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$course_id = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$user_id = $_SESSION['user_id'];

if (!$course_id) {
    $_SESSION['error'] = 'No course selected';
    redirect('my-courses.php');
}

// Check if user is enrolled in this course
$conn = getDBConnection();
$enrollment_sql = "SELECT e.*, c.title, c.slug, c.thumbnail, c.full_description
                   FROM enrollments e
                   JOIN courses c ON e.course_id = c.id
                   WHERE e.user_id = ? AND e.course_id = ? AND e.payment_status = 'completed'";
$enrollment_stmt = $conn->prepare($enrollment_sql);
$enrollment_stmt->bind_param("ii", $user_id, $course_id);
$enrollment_stmt->execute();
$enrollment_result = $enrollment_stmt->get_result();

if ($enrollment_result->num_rows === 0) {
    $_SESSION['error'] = 'You are not enrolled in this course';
    redirect('my-courses.php');
}

$enrollment = $enrollment_result->fetch_assoc();
$course = $enrollment;

// Get course modules and videos
$modules_sql = "SELECT m.*,
                       (SELECT COUNT(*) FROM videos v WHERE v.module_id = m.id) as video_count,
                       (SELECT SUM(duration_minutes) FROM videos v WHERE v.module_id = m.id) as total_minutes
               FROM modules m WHERE m.course_id = ? ORDER BY m.module_order";
$modules_stmt = $conn->prepare($modules_sql);
$modules_stmt->bind_param("i", $course_id);
$modules_stmt->execute();
$modules_result = $modules_stmt->get_result();
$modules = [];
while ($module = $modules_result->fetch_assoc()) {
    $modules[] = $module;
}

// Get user progress
$progress_sql = "SELECT video_id, progress_percentage FROM user_progress WHERE user_id = ? AND course_id = ?";
$progress_stmt = $conn->prepare($progress_sql);
$progress_stmt->bind_param("ii", $user_id, $course_id);
$progress_stmt->execute();
$progress_result = $progress_stmt->get_result();
$user_progress = [];
while ($progress = $progress_result->fetch_assoc()) {
    $user_progress[$progress['video_id']] = $progress['progress_percentage'];
}

// Calculate overall progress
$total_videos = 0;
$completed_videos = 0;
foreach ($modules as $module) {
    $videos_sql = "SELECT id FROM videos WHERE module_id = ?";
    $videos_stmt = $conn->prepare($videos_sql);
    $videos_stmt->bind_param("i", $module['id']);
    $videos_stmt->execute();
    $videos_result = $videos_stmt->get_result();

    while ($video = $videos_result->fetch_assoc()) {
        $total_videos++;
        if (isset($user_progress[$video['id']]) && $user_progress[$video['id']] >= 90) {
            $completed_videos++;
        }
    }
}

$overall_progress = $total_videos > 0 ? round(($completed_videos / $total_videos) * 100) : 0;

$page_title = "Learning: " . $course['title'];
include '../includes/header.php';
?>

<?php if (isset($_SESSION['success'])): ?>
<div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb; text-align: center; font-weight: 500;">
    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb; text-align: center; font-weight: 500;">
    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<div class="learning-container">
    <!-- Course Header -->
    <div class="course-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white; padding: 30px 0; margin-bottom: 30px;">
        <div class="container">
            <div style="display: flex; gap: 30px; align-items: flex-start;">
                <img src="../assets/images/courses/<?php echo $course['thumbnail'] ?: 'default-course.jpg'; ?>"
                     alt="<?php echo $course['title']; ?>"
                     style="width: 120px; height: 90px; object-fit: cover; border-radius: 8px;">

                <div style="flex: 1;">
                    <h1 style="margin: 0 0 10px 0; font-size: 2rem;"><?php echo htmlspecialchars($course['title']); ?></h1>
                    <p style="margin: 0 0 15px 0; opacity: 0.9;"><?php echo htmlspecialchars($course['full_description']); ?></p>

                    <div style="display: flex; gap: 30px; align-items: center;">
                        <div>
                            <strong>Progress:</strong> <?php echo $overall_progress; ?>%
                            <div style="width: 200px; height: 8px; background: rgba(255,255,255,0.3); border-radius: 4px; margin-top: 5px;">
                                <div style="width: <?php echo $overall_progress; ?>%; height: 100%; background: white; border-radius: 4px;"></div>
                            </div>
                        </div>

                        <div><strong>Enrolled:</strong> <?php echo date('M j, Y', strtotime($enrollment['enrolled_at'])); ?></div>

                        <a href="my-courses.php" class="btn-back" style="background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; border-radius: 5px; text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> Back to My Courses
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="learning-layout" style="display: grid; grid-template-columns: 1fr 350px; gap: 30px;">

            <!-- Main Content -->
            <div class="learning-content">
                <?php if (empty($modules)): ?>
                    <div class="empty-state" style="text-align: center; padding: 60px 20px; color: var(--gray-color);">
                        <i class="fas fa-book fa-4x" style="margin-bottom: 20px; opacity: 0.5;"></i>
                        <h3>Course content coming soon</h3>
                        <p>The course materials are being prepared. Check back later!</p>
                    </div>
                <?php else: ?>
                    <!-- Course Modules -->
                    <div class="course-modules">
                        <?php foreach ($modules as $module_index => $module):
                            $videos_sql = "SELECT * FROM videos WHERE module_id = ? ORDER BY video_order";
                            $videos_stmt = $conn->prepare($videos_sql);
                            $videos_stmt->bind_param("i", $module['id']);
                            $videos_stmt->execute();
                            $videos_result = $videos_stmt->get_result();
                            $videos = [];
                            while ($video = $videos_result->fetch_assoc()) {
                                $videos[] = $video;
                            }
                        ?>
                        <div class="module-card" style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden;">
                            <div class="module-header" style="background: var(--light-color); padding: 20px; border-bottom: 1px solid var(--border-color); cursor: pointer;" onclick="toggleModule(<?php echo $module_index; ?>)">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h3 style="margin: 0 0 5px 0; color: var(--primary-color);"><?php echo htmlspecialchars($module['title']); ?></h3>
                                        <?php if ($module['description']): ?>
                                        <p style="margin: 0; color: var(--gray-color); font-size: 0.9rem;"><?php echo htmlspecialchars($module['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="color: var(--gray-color); font-size: 0.9rem;">
                                            <?php echo count($videos); ?> lessons • <?php echo $module['total_minutes']; ?> min
                                        </div>
                                        <i class="fas fa-chevron-down" id="module-icon-<?php echo $module_index; ?>" style="margin-top: 10px; color: var(--gray-color);"></i>
                                    </div>
                                </div>
                            </div>

                            <div id="module-content-<?php echo $module_index; ?>" class="module-content" style="display: none;">
                                <?php if (empty($videos)): ?>
                                    <div style="padding: 20px; text-align: center; color: var(--gray-color);">
                                        <i class="fas fa-video-slash"></i> No videos available yet
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($videos as $video): ?>
                                    <div class="video-item" style="padding: 15px 20px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 15px; cursor: pointer;" onclick="playVideo(<?php echo $video['id']; ?>, '<?php echo addslashes($video['title']); ?>', '<?php echo $video['video_url']; ?>')">
                                        <div class="video-play-icon" style="width: 40px; height: 40px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                            <i class="fas fa-play"></i>
                                        </div>

                                        <div style="flex: 1;">
                                            <div style="font-weight: 500; color: var(--dark-color); margin-bottom: 5px;">
                                                <?php echo htmlspecialchars($video['title']); ?>
                                                <?php if ($video['is_preview']): ?>
                                                <span style="background: var(--accent-color); color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.7rem;">Preview</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($video['description']): ?>
                                            <div style="color: var(--gray-color); font-size: 0.9rem;">
                                                <?php echo htmlspecialchars(substr($video['description'], 0, 100)); ?>...
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <div style="text-align: right;">
                                            <div style="color: var(--gray-color); font-size: 0.9rem;">
                                                <?php echo $video['duration_minutes']; ?> min
                                            </div>
                                            <?php
                                            $progress = isset($user_progress[$video['id']]) ? $user_progress[$video['id']] : 0;
                                            if ($progress >= 90) {
                                                echo '<i class="fas fa-check-circle" style="color: var(--success-color);"></i>';
                                            } elseif ($progress > 0) {
                                                echo '<i class="fas fa-play-circle" style="color: var(--warning-color);"></i>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="learning-sidebar">
                <!-- Video Player -->
                <div class="video-player-card" style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px;">
                    <h4 style="margin-bottom: 15px; color: var(--primary-color);">Video Player</h4>
                    <div id="video-player" style="background: #000; border-radius: 8px; height: 200px; display: flex; align-items: center; justify-content: center; color: white;">
                        <div style="text-align: center;">
                            <i class="fas fa-play-circle fa-3x" style="margin-bottom: 10px; opacity: 0.7;"></i>
                            <p>Select a video to start learning</p>
                        </div>
                    </div>
                    <div id="video-info" style="margin-top: 15px; display: none;">
                        <h5 id="current-video-title" style="margin: 0 0 10px 0; color: var(--primary-color);"></h5>
                        <div style="display: flex; gap: 10px;">
                            <button onclick="markAsCompleted()" class="btn btn-success" style="flex: 1; padding: 8px;">
                                <i class="fas fa-check"></i> Mark Complete
                            </button>
                            <button onclick="updateProgress()" class="btn btn-primary" style="flex: 1; padding: 8px;">
                                <i class="fas fa-save"></i> Save Progress
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Course Progress -->
                <div class="progress-card" style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px;">
                    <h4 style="margin-bottom: 15px; color: var(--primary-color);">Your Progress</h4>

                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span>Overall Progress</span>
                            <span><?php echo $overall_progress; ?>%</span>
                        </div>
                        <div style="width: 100%; height: 10px; background: var(--light-color); border-radius: 5px;">
                            <div style="width: <?php echo $overall_progress; ?>%; height: 100%; background: linear-gradient(90deg, var(--accent-color) 0%, var(--success-color) 100%); border-radius: 5px;"></div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; text-align: center;">
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);"><?php echo $completed_videos; ?></div>
                            <div style="color: var(--gray-color); font-size: 0.9rem;">Completed</div>
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);"><?php echo $total_videos - $completed_videos; ?></div>
                            <div style="color: var(--gray-color); font-size: 0.9rem;">Remaining</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentVideoId = null;

function toggleModule(index) {
    const content = document.getElementById('module-content-' + index);
    const icon = document.getElementById('module-icon-' + index);

    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

function playVideo(videoId, title, videoUrl) {
    currentVideoId = videoId;

    // Update video player
    const player = document.getElementById('video-player');
    player.innerHTML = `
        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #000; color: white;">
            <div style="text-align: center;">
                <i class="fas fa-play-circle fa-2x" style="margin-bottom: 10px;"></i>
                <p>Playing: ${title}</p>
                <small>Video URL: ${videoUrl}</small>
            </div>
        </div>
    `;

    // Show video info
    document.getElementById('video-info').style.display = 'block';
    document.getElementById('current-video-title').textContent = title;

    // Mark as started (save progress)
    updateProgress();
}

function markAsCompleted() {
    if (!currentVideoId) return;

    fetch('update-progress.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `video_id=${currentVideoId}&progress=100`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Video marked as completed!');
            location.reload();
        } else {
            alert('Error updating progress');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating progress');
    });
}

function updateProgress() {
    if (!currentVideoId) return;

    fetch('update-progress.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `video_id=${currentVideoId}&progress=50`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Progress saved');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Initialize first module as open
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.module-card')) {
        toggleModule(0);
    }
});
</script>

<?php include '../includes/footer.php'; ?>
