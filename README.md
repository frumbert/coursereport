```
$shape = <<<SHAPE;
	+--------------------------------------------------------------+
	|Users   | course1     | course2       | course3               |  <- sticky row
	|        | act1 | act2 | act 1 | act 2 | act 1 | act 2 | act 3 |  <- sticky row
	|group 1 |      |      |       |       |       |       |       |
	|user1   |   x  |   x  |   x   |   o   |    x  |    x  |   x   |
	|user2   |   x  |   x  |   x   |   o   |    x  |    x  |   x   |
	|user3   |   x  |   x  |   o   |   o   |    o  |    x  |   o   |
	|group 2 |      |      |       |       |       |       |       |
	|user1   |   x  |   x  |   x   |   o   |    x  |    x  |   x   |
	|user2   |   x  |   x  |   x   |   o   |    x  |    x  |   x   |
	|user3   |   x  |   x  |   o   |   o   |    o  |    x  |   o   |

       ^
       |
  sticky column
```

  	* the 
maximum number of columns is the number of activities in the course that can have completion set times the number of courses plus 1
  	* the number of rows is the number of users in the course for that year plus the number of groups plus 2
  	* sticky tables will be using the bootstrap sticky feature https://examples.bootstrap-table.com/#extensions/fixed-columns.html
