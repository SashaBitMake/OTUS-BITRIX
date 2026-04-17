<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->SetTitle('Datamanager в Битрикс');
$APPLICATION->SetAdditionalCSS('/homeworks/homework4/styles.css');

use App\Models\ClassroomsValuesTable as classes;
use App\Models\StudentsValuesTable as Student;
use App\Models\TeachersValuesTable as Teacher;
?>

<h3 class="section-title">Расписание студентов (с временем из сводной таблицы)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th>Студент</th>
            <th>Аудитория</th>
            <th>Поток</th>
            <th>Время</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $scheduleData = Student::getList([
            'select' => [
                'ELEMENT',
                'SCHEDULE',
                'FLOW_ID',
                'SCHEDULE.TIME_CLASS',
                'SCHEDULE.CLASSROOM',
                'SCHEDULE.CLASSROOM.ELEMENT.NAME',
            ]
        ])->fetchCollection();

        foreach ($scheduleData as $student) {
            $studentName = $student->getElement()->getName();
            $scheduleItems = $student->getSchedule();
            $studentFlow = $student->getFlowId();

            if (count($scheduleItems) > 0) {
                foreach ($scheduleItems as $item) {
                    $room = $item->getClassroom();
                    $roomName = $room->getElement()->getName();
                    $time = $item->getTimeClass();

                    echo "<tr>
                            <td>{$studentName}</td>
                            <td>{$roomName}</td>
                            <td>$studentFlow</td>
                            <td>{$time}</td>
                          </tr>";
                }
            } else {
                echo "<tr>
                        <td>{$studentName}</td>
                        <td>—</td>
                        <td>—</td>
                      </tr>";
            }
        }
        ?>
    </tbody>
</table>

<h3 class="section-title">Закреплённые преподаватели за Аудиториями(Связь 1:1)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th>Аудитория</th>
            <th>Закреплённый преподаватель</th>
            <th>Специальность преподователя</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $collectionRoom = classes::getList([
            'select' => [
                'ELEMENT',
                'TEACHER.ELEMENT',
                'TEACHER.SPEC_ID'
            ]
        ])->fetchCollection();
        
        foreach ($collectionRoom as $class) {
            $roomName = $class->getElement()->getName();
            $teacherName = $class->getTeacher()->getElement()->getName();
            $teacherSpec = $class->getTeacher()->getSpecId();
            echo "<tr>
                    <td>{$roomName}</td>
                    <td>{$teacherName}</td>
                    <td>{$teacherSpec}</td>
                  </tr>";
        }
        ?>
    </tbody>
</table>


<h3 class="section-title">Студенты и их кураторы(Один-ко-многим)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th>Студент</th>
            <th>Поток</th>
            <th>Куратор</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $collection = Student::getList([
            'select' => [
                'IBLOCK_ELEMENT_ID',
                'ID_TEACHER',
                'FLOW_ID',
                'ELEMENT',
                'TEACHER.ELEMENT'
            ]
        ])->fetchCollection();

        foreach ($collection as $stud) {
            $studentName = $stud->getElement()->getName();
            $flow = $stud->getFlowId();
            $teacherName = $stud->getTeacher()->getElement()->getName();
            echo "<tr>
                    <td>{$studentName}</td>
                    <td>{$flow}</td>
                    <td>{$teacherName}</td>
                  </tr>";
        }
        ?>
    </tbody>
</table>

<h3 class="section-title">Кураторы и закреплённые студенты(Один-ко-многим)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th>Куратор</th>
            <th>Студенты</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $teachersCollection = Teacher::getList([
            'select' => [
                '*',
                'IBLOCK_ELEMENT_ID',
                'ELEMENT',
                'STUDENTS',
                'STUDENTS.ELEMENT',
            ]
        ])->fetchCollection();

        foreach ($teachersCollection as $teacher) {
            $teacherName = $teacher->getElement()->getName();
            $students = $teacher->getStudents();
            $studentsList = '';
            if (count($students) > 0) {
                $studentsList .= '<ul>';
                foreach ($students as $student) {
                    $studentsList .= '<li>' . $student->getElement()->getName() . '</li>';
                }
                $studentsList .= '</ul>';
            } else {
                $studentsList = 'Нет студентов';
            }
            echo "<tr>
                    <td>{$teacherName}</td>
                    <td>{$studentsList}</td>
                  </tr>";
        }
        ?>
    </tbody>
</table>

<h3 class="section-title">Комнаты и студенты (Многие-ко-многим, через таблицу schedule)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th>Аудитория</th>
            <th>Студенты, которые её посещают</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $collectionSchedule = classes::getList([
            'select' => [
                '*',
                'ELEMENT',
                'STUDENT',
                'STUDENT.ELEMENT.NAME',
            ]
        ])->fetchCollection();

        foreach ($collectionSchedule as $room) {
            $roomName = $room->getElement()->getName();
            $students = $room->getStudent();
            $studentsList = '';
            if (count($students) > 0) {
                $studentsList .= '<ul>';
                foreach ($students as $student) {
                    $studentsList .= '<li>' . $student->getElement()->getName() . '</li>';
                }
                $studentsList .= '</ul>';
            } else {
                $studentsList = 'Нет студентов';
            }
            echo "<tr>
                    <td>{$roomName}</td>
                    <td>{$studentsList}</td>
                  </tr>";
        }
        ?>
    </tbody>
</table>

<h3 class="section-title">Студенты и посещаемые комнаты (Многие-ко-многим, через таблицу schedule)</h3>
<table class="data-table">
    <thead>
        <tr>
            <th>Студент</th>
            <th>Посещаемые комнаты</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $collectionScheduleStudents = Student::getList([
            'select' => [
                '*',
                'ELEMENT',
                'ROOMS',
                'ROOMS.ELEMENT.NAME',
            ]
        ])->fetchCollection();

        foreach ($collectionScheduleStudents as $student) {
            $studentName = $student->getElement()->getName();
            $rooms = $student->getRooms();
            $roomsList = '';
            if (count($rooms) > 0) {
                $roomsList .= '<ul>';
                foreach ($rooms as $room) {
                    $roomsList .= '<li>' . $room->getElement()->getName() . '</li>';
                }
                $roomsList .= '</ul>';
            } else {
                $roomsList = 'Нет комнат';
            }
            echo "<tr>
                    <td>{$studentName}</td>
                    <td>{$roomsList}</td>
                  </tr>";
        }
        ?>
    </tbody>
</table>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>