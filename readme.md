# Collect

For nearly as long as I can remember, I've been trying to simplify my programming projects by breaking down long repetitive tasks down into as short and concise steps as I can. I shudder to think of the hours in my life spent putting together a web page that reads a list of items from a database, filters or transforms them, then renders out the appropriate html and attachments. So many times have I done this, across many languages, projects, and technologies. 

For the past two years I've been reforming my interest in web, to an interest in mobile web. During this time, I've also run through all my regular distractions like creating a little mobile RSS reader, a mobile client profile database for a cousin, a mobile plumbing inspection checklist for another friend. All these very different projects have a lot in common. They need to capture, edit, delete and list objects. Throw in a little menu navigation and there you have a system. My goal with this PHP/JQM MVC project is to turn my dev cycle for small projects into 90% deciding what I want, 10% typing it in.

Now since I'm targeting mobile devices, form layout can simply default to a vertical list of form controls down the page. All that's really left is deciding what data needs to be captured in my simple data capture app. Here's an example set of definitions...

## The Client Profile Demo

```javascript
{
"client": {
	"field": {
		"firstname": { "type": "string", "req": true, "min": 2, "max": 50, "regex": "text", "description": "First Name" },
		"lastname": { "type": "string", "req": true, "min": 2, "max": 50, "regex": "text", "description": "Last Name" },
		"email": { "type": "string", "req": true, "regex": "email" },
		"portfolio": { "type": "integer", "control": "input", "default": "", "req": false },
		"clientproduct": { "type": "rel-many", "description": "Products this client uses", "control": "checklist" },
		"clientstate": { "type": "integer", "req": false, "control": "select", "description": "State" }
	},
	"view": {
		"default": {
			"field": [{"source":"lastname"}, {"source":"firstname"}]
		},
		"disabled": {
			"title": "R1m+",
			"field": [{"source":"lastname"}, {"source":"portfolio"}],
			"filter": [{"a":"portfolio", "o":">", "b":"1000000" }]
		}
	}
},
"clientstate": {
	"field": {
		"name": { "type": "string", "req": true, "min": 2, "max": 15, "regex": "text", "description": "The gender name." }
	},
	"insert": [{ "id": 1, "name": "Current" }, { "id": 2, "name": "Potential" }, { "id": 3, "name": "Dormant" }, { "id": 4, "name": "Cancelled" }]
},
"clientproduct": {
	"field": {
		"name": { "type": "string", "req": true, "min": 2, "max": 25, "regex": "text", "description": "The product name." }
	},
	"insert": [{ "id": 1, "name": "Capital Investment" },{ "id": 2, "name": "Stocks" },{ "id": 3, "name": "Insurance" },{ "id": 4, "name": "Hedge Fund" }]
}
}
```

There are three object models there: A client, a client state, and a product. Clients have several details, among which is a state. They can be in only one state at a time. Clients can have many products. That may or may not look like a lot,.. but what does it get you? 
* <b>Six tables in your database</b>, three data tables, each with a respective log table tracking all changes
* An <b>API on the serverside</b> to manipulate that data
* <b>Clientside forms</b> to edit instances of each of the models
* Some simple <b>regex error detection</b> in the forms
* <b>Overview and list pages</b> to navigate the data
* <b>A dashboard</b> to hold it all together
* The login procedure and managing users are also thrown in already

A screengrab of the edit form for the above client model.
![](http://2.bp.blogspot.com/-MzfsYbjzwNU/UzlZ232OdYI/AAAAAAAAAgo/kJxZOydbHJc/s1600/client_edit.PNG)


## Download and Setup
You can freely download, copy, hack and sell this project. To setup your own mobile app, the general process is as follows:
* Copy this project folder into your htdocs or respective web directory
* Edit the <b>app/schema.json</b> file to define your models and views
* Delete any existing <b>app/database.sqlite</b> files (if you want to start from a clean slate)
* Open <b>api/check.php</b> in a browser. This will create any tables that don't exist
* Now open your mobile web app in a browser window

## Some Caveats
As per usual, I offer this simply as a mostly working prototype. I was actively working on this project more than a year ago, but since then time and priority has led to the inevitable disregard of maintaining it. I still use it often when I need a little admin panel for a website or project I'm working on. I find it workable, but there are definitely bugs, and features half baked in. Have fun with it.

## License

(The MIT License)

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


