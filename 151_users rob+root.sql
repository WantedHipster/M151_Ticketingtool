# Privileges for `rob`@`localhost`
2
​
3
GRANT USAGE ON *.* TO `rob`@`localhost` IDENTIFIED BY PASSWORD '*14F1A8C42F8B6D4662BB3ED290FD37BF135FE45C';
4
​
5
GRANT ALL PRIVILEGES ON `151_users`.* TO `rob`@`localhost`;
6
​
7
​
8
# Privileges for `root`@`localhost`
9
​
10
GRANT ALL PRIVILEGES ON *.* TO `root`@`localhost` WITH GRANT OPTION;
11
​
12
GRANT PROXY ON ''@'%' TO 'root'@'localhost' WITH GRANT OPTION;