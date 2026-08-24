ALTER TABLE `students`
  MODIFY `course_id` bigint unsigned NULL;

ALTER TABLE `users`
  MODIFY `email` varchar(255) NULL;

ALTER TABLE `students`
  MODIFY `faculty_id` bigint unsigned NULL,
  MODIFY `department_id` bigint unsigned NULL,
  MODIFY `academic_level_id` bigint unsigned NULL,
  MODIFY `academic_session_id` bigint unsigned NULL;

UPDATE `students` s
JOIN `users` u ON u.`id` = s.`user_id`
LEFT JOIN `student_placements` sp ON sp.`student_id` = s.`id`
SET
  s.`faculty_id` = NULL,
  s.`department_id` = NULL,
  s.`course_id` = NULL,
  s.`academic_level_id` = NULL,
  s.`academic_session_id` = NULL
WHERE u.`email` LIKE '%@students.coousiwes.local'
  AND s.`metadata` IS NULL
  AND s.`gender` IS NULL
  AND s.`date_of_birth` IS NULL
  AND s.`address` IS NULL
  AND sp.`id` IS NULL;

UPDATE `users`
SET `email` = NULL
WHERE `email` LIKE '%@students.coousiwes.local';
