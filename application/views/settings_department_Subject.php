<!-- settings_department_Subject.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Course Subjects</title>
    <!-- Add your stylesheets and scripts here -->
    <link rel="stylesheet" href="<?= base_url('assets/css/mobile-shell.css?v=6'); ?>">
    <meta name="theme-color" content="#1a2942">
    <link rel="manifest" href="<?= base_url('manifest.webmanifest?v=3'); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="<?= base_url('assets/images/icons/attendance-192.png'); ?>">
    <script src="<?= base_url('assets/js/mobile-shell-early.js?v=5'); ?>"></script>
</head>
<body>
    <div class="container">
        <h1>Subjects for <?= isset($course) ? $course->CourseDescription : 'Selected Course' ?></h1>
        
        <!-- Check if data is available -->
        <?php if (!empty($data)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Major</th>
                        <th>Year Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $course_subject): ?>
                        <tr>
                            <td><?= $course_subject->SubjectCode; ?></td>
                            <td><?= $course_subject->SubjectName; ?></td>
                            <td><?= $course_subject->Major; ?></td>
                            <td><?= $course_subject->YearLevel; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No subjects found for this course.</p>
        <?php endif; ?>

        <!-- Year Level Selection -->
        <h2>Select Year Level</h2>
        <form method="get" action="<?= base_url('Settings/displaysubByCourse/' . $courseid); ?>">
            <select name="year_level">
                <?php if (!empty($yearLevels)): ?>
                    <?php foreach ($yearLevels as $level): ?>
                        <option value="<?= $level->id; ?>"><?= $level->name; ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <button type="submit">Filter</button>
        </form>
    </div>
    <script src="<?= base_url('assets/js/mobile-shell.js?v=5'); ?>"></script>
</body>
</html>
