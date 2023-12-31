<?php

require_once("./functions/employer_auth.php");
checkEmployerId();

$db = $GLOBALS["db"];

// Check whether this employer is allowed to edit this posting.
$statement = new mysqli_stmt($db, "SELECT * FROM Job WHERE JobID = ?");
$statement->bind_param("s", $jobId);
$success = $statement->execute();

if (!$success) {
    echo "An error happened. Please try again.";
    exit;
}

$result = $statement->get_result();

if ($result->num_rows === 0) {
    echo "Job not found.";
    exit;
}

$job = $result->fetch_assoc();

if ($job["CompanyID"] !== $_SESSION["employerId"]) {
    echo "You do not have permission to view this page.";
    exit;
}

// Gets the current job information.
$jobTitle = $job["JobTitle"];
$specialization = $job["SpecializationID"];
$deadline = DateTimeImmutable::createFromFormat("Y-m-d H:i:s", $job["ApplicationDeadline"])->format("Y-m-d\\TH:i");
$salary = $job["Salary"];
$workLocation = $job["WorkingLocation"];
$experience = $job["ExperienceRequirement"];
$format = $job["WorkingFormat"];
$scope = $job["ScopeOfWork"];
$benefits = $job["Benefits"];

$validExperiences = array("Internship", "Entry level", "Junior", "Mid-level", "Senior");
$validFormats = array("On-site", "Remote", "Hybrid");
$errors = array();

// Handles the post request.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (
        !isset($_POST, $_POST["jobTitle"], $_POST["specialization"], $_POST["deadline"], $_POST["salary"],
        $_POST["workLocation"], $_POST["experience"], $_POST["format"], $_POST["scope"], $_POST["benefits"])
    ) {
        header("Location: /employer/edit-job/" . $jobId);
        exit;
    }

    $jobTitle = trim($_POST["jobTitle"]);
    $specialization = trim($_POST["specialization"]);
    $deadline = trim($_POST["deadline"]);
    $salary = trim($_POST["salary"]);
    $workLocation = trim($_POST["workLocation"]);
    $experience = trim($_POST["experience"]);
    $format = trim($_POST["format"]);
    $scope = trim($_POST["scope"]);
    $benefits = trim($_POST["benefits"]);

    if ($jobTitle === "") {
        array_push($errors, "Please specify the job title.");
    }
    if (!preg_match("/^\d+$/", $specialization)) {
        array_push($errors, "Please select a valid specialization.");
    }
    if ($deadline === "") {
        array_push($errors, "Please choose a valid deadline.");
    }
    $deadlineDt = DateTimeImmutable::createFromFormat("Y-m-d\\TH:i", $deadline);
    if ($deadlineDt === false) {
        array_push($errors, "Please make sure your deadline is in the correct format.");
    }
    if (!preg_match("/(^\d{1,8}$)|(^\d{1,8}\.\d{1,2}$)/", $salary)) {
        array_push($errors, "Please enter a valid salary value. It can have up to five digits before the decimal point and up to two digits after the decimal point.");
    }
    if ($workLocation === "") {
        array_push($errors, "Please specify a working location.");
    }
    if (!in_array($experience, $validExperiences)) {
        array_push($errors, "Please specify a vaid experience requirement.");
    }
    if (!in_array($format, $validFormats)) {
        array_push($errors, "Please specify a valid working format.");
    }
    if ($scope === "") {
        array_push($errors, "Please specify the scope of work.");
    }
    if ($benefits === "") {
        array_push($errors, "Please specify the work benefits.");
    }

    if (count($errors) === 0) {
        $deadline = $deadlineDt->format("Y-m-d H:i:s");

        $statement = new mysqli_stmt($db, "UPDATE Job 
                                           SET JobTitle = ?, ApplicationDeadline = ?, Salary = ?, WorkingLocation = ?, SpecializationID = ?, ExperienceRequirement = ?, WorkingFormat = ?, ScopeOfWork = ?, Benefits = ?
                                           WHERE JobID = ?");
        $statement->bind_param("ssssssssss", $jobTitle, $deadline, $salary, $workLocation, $specialization, $experience, $format, $scope, $benefits, $jobId);
        $success = $statement->execute();

        if (!$success) {
            array_push($errors, "An error happened. Please check your input and try again.");
        } else {
            echo "Job updated successfully!";
        }
    }
}

$statement = new mysqli_stmt($db, "SELECT * FROM Specialization");
$statement->execute();
$specializations = $statement->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Edit job posting - GreeLiving</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>
    <link href="/assets/css/header.css" rel="stylesheet" />
    <link href="/assets/css/footer.css" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/job_post.css">
</head>

<body>

    <?php require("./components/header_employer.php") ?>

    <main style="padding-top:100px">

        <h1>Edit job posting</h1>

        <?php foreach ($errors as $error): ?>
            <p class="text-danger">
                <?= $error ?>
            </p>
        <?php endforeach; ?>

        <form method="post" action="">
            <div class="formContainer">
                <div class="form-control">
                    <label>
                        Job title: <input type="text" name="jobTitle" value="<?= $jobTitle ?>" />
                    </label>

                    <label>
                        Specialization:
                        <select name="specialization">
                            <?php foreach ($specializations as $specializationOption): ?>
                                <option value="<?= $specializationOption["SpecializationID"] ?>"
                                    <?= $specializationOption["SpecializationID"] == $specialization ? " selected" : "" ?>>
                                    <?= $specializationOption["SpecializationName"] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        Application deadline: <input type="datetime-local" name="deadline" value="<?= $deadline ?>" />
                    </label>

                    <label>
                        Salary: <input type="text" name="salary" value="<?= $salary ?>" />
                    </label>

                    <label>
                        Working location: <input type="text" name="workLocation" value="<?= $workLocation ?>" />
                    </label>

                    <label>
                        Experience requirement:
                        <select name="experience">
                            <?php foreach ($validExperiences as $validExperience): ?>
                                <option value="<?= $validExperience ?>" <?= $validExperience == $experience ? " selected" : "" ?>>
                                    <?= $validExperience ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        Working format:
                        <select name="format">
                            <?php foreach ($validFormats as $validFormat): ?>
                                <option value="<?= $validFormat ?>" <?= $validFormat == $format ? " selected" : "" ?>>
                                    <?= $validFormat ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        Scope of work:
                        <textarea name="scope"><?= $scope ?></textarea>
                    </label>

                    <label>
                        Benefits:
                        <textarea name="benefits"><?= $benefits ?></textarea>
                    </label>

                </div>
                <button class="submitbtn" type="submit">Edit</button>
            </div>


        </form>

    </main>

    <?php require("./components/footer.php") ?>

</body>

</html>