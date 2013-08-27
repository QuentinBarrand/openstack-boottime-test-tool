DROP TABLE IF EXISTS spawntime_vm;
DROP TABLE IF EXISTS spawntime_test;

CREATE TABLE spawntime_vm(
id int,
test_id text,
boot text,
ready text,
configured text)
ENGINE=InnoDB;

CREATE TABLE spawntime_test(
id int not null auto_increment,
image text,
flavor text,
rounds int,
dns text,
infrastructure text,
PRIMARY KEY(id))
ENGINE=InnoDB;