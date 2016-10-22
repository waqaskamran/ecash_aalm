alter table `queue` add column `lock_sequence` int(11);
alter table `time_zone` add column `dst` char(1);
alter table `time_zone` add column `tz` int(11);
insert into `time_zone` (`gmt_offset`, `name`, `dst`, `tz`) VALUES
("-4.00","US/Atlantic","Y",4),
("-5.00","US/Eastern","Y",5),
("-6.00","US/Central","Y",6),
("-7.00","US/Mountain","Y",7),
("-7.00","US/Arizona","N",7),
("-8.00","US/Pacific","Y",8),
("-9.00","America/Whitehorse","Y",9),
("-10.00","US/Alaska","Y",10),
("-11.00","UTC/GMT-11","N",11),
("11.00","UTC/GMT+11","N",13),
("10.00","UTC/GMT+10","N",14),
("9.00","UTC/GMT+9","N",15);


