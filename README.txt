/**
 * @version		$Id: mongosql.php 20806 2011-05-15 19:44:59Z drahmel $
 * @package		mongosql
 * @copyright	Copyright (C) 2011 Dan Rahmel All rights reserved.
 * @license		Apache license
 */

 Querying Mongo with JSON-based syntax is non-intuitive, difficult to type (requires many shift keys and non-standard {} characters, difficult to read, and requires a lot of practice. In contrast, SQL is much more English-like,  easily typed and easy to read. So why not put a SQL query front-end on Mongo and get the best of both worlds?
 	 
 This is a very early attempt at that. It doesn't even have an actual parser -- it brute-forces the parsing with regular expressions and string operations. However, it is a step in the right direction. Perhaps someone with the extra time and skills can fork the project and add a proper parser. 