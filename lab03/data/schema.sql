CREATE TABLE IF NOT EXISTS `user` (
    `id` INTEGER NOT NULL PRIMARY KEY,
    `name` TEXT NOT NULL,
    `surname` TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS `lecture` (
    `id` INTEGER NOT NULL PRIMARY KEY,
    `code` TEXT NOT NULL,
    `name` TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS `student` (
    `user_id` INTEGER NOT NULL,
    `lecture_id` INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS `teacher` (
    `user_id` INTEGER NOT NULL,
    `lecture_id` INTEGER NOT NULL
);
