<?php

/**
 * A data model provides application specific interface and implement all operations
 * using NotORM database abstraction tool.
 */
class Model
{
    /**
     * @var NotORM
     */
    private $norm;

    public function __construct(NotORM $norm)
    {
        $this->norm = $norm;
    }

    /**
     * Retrieve all users ordered by their surnames (first) and names (second).
     * @return array keys are user IDs, values are strings (name + surname with space in between)
     */
    public function getUsers(): array
    {
        $result = [];

        foreach ($this->norm->user()->order("surname")->order("name") as $usr) { // get all applications
            $result[$usr['id']] = "$usr[name] $usr[surname]";
        }

        return $result;
    }

    /**
     * Retrieve a complete list of all lectures.
     * @return array keys are lecture IDs, values are associative arrays representing individual lectures
     *               with the folowing keys: 'code', 'name', 'students' (number of enrolled students)
     */
    public function getAllLectures(): array
    {
        $result = [];

        foreach($this->norm->lecture() as $lecture) {
            $result[$lecture['id']]['code'] = $lecture['code'];
            $result[$lecture['id']]['name'] = $lecture['name'];
            $result[$lecture['id']]['students'] = $this->norm->student()->where("lecture_id", $lecture['id'])->count("*");
        }

        return $result;
    }

    /**
     * Retrieve a list of teachers for every lecture (order does not matter).
     * @return array keys are lecture codes, values are arrays holding teacher names
     *               (names are concatenated strings name + surname with space in between)
     */
    public function getLecturesTeachers(): array
    {
        $result = [];

        foreach($this->norm->user() as $usr) {
            foreach($this->norm->teacher()->where("user_id", $usr['id']) as $teaches) {
                $lec_code = $this->norm->lecture()->select('code')->where("id", $teaches['lecture_id'])->fetch()['code'];
                // echo ($lec_code); //['code'];
                $result[$lec_code][] = "$usr[name] $usr[surname]";
            }
        }

        return $result;
    }

    /**
     * Tetrieve a list of enrolled students for particular lecture.
     * @param string $code of the lecture (this is not PK!)
     * @return array keys are user IDs, values are concatenated strings name + surname with space in between
     */
    public function getEnrolledStudents(string $code): array
    {
        $result = [];

        foreach($this->norm->user()->where("id", 
            $this->norm->student()->select("user_id")->where("lecture_id",
            $this->norm->lecture()->where("code", $code)->fetch()['id'])) as $student) {
                $result[$student['id']] = "$student[name] $student[surname]";
            }

        return $result;
    }

    /**
     * Enroll a student for a particular lecture. If the student is already enrolled
     * or the lecture does not exist, nothing happens.
     * @param int $userId ID of a user to be enrolled
     * @param string $lectureCode (this is not PK!)
     */
    public function enrollStudent(int $userId, string $lectureCode): void
    {
        $lec_id = $this->norm->lecture()->where('code', $lectureCode)->fetch()['id'];
        if ($lec_id !== null && $this->norm->student()->where('user_id', $userId)->where('lecture_id',$lec_id)->count() === 0) {
            $this->norm->student()->insert(array('user_id' => $userId, 'lecture_id' => $lec_id));
        }
    }

    /**
     * Updates the name of a lecture with given code.
     * @param string $code of the lecture (this is not PK!)
     * @param string $newName updated name of the lecture
     * @return string|null old name (before it was changed), null if the lecture does not exist
     */
    public function updateLectureName(string $code, string $newName): ?string
    {

        $res = $this->norm->lecture()->where('code', $code)->fetch();
        
        if($res !== null){
            $old = $res['name'];
            $res['name'] = $newName;
            $res->update();

            return $old;
        } 
        else {
            return null;
        }
    }
}
