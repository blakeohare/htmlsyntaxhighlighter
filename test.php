<?php

    require_once 'TextUtil.php';
    require_once 'syntax_highlighter.php';
    require_once 'htmlplusplus.php';

    $content = '

    Hello, <span style="color:#f00;">World</span>!

    <tableofcontents/>

    <code class="syntax_vscode" language="json">
 {
    "foo": [1, 2, 3.14, true, false, null],
    "bar": "This is a \n\t\u1234String"
}
    </code>

    <code class="syntax_vscode" language="javascript">
function sayHello() {
  console.log("Hello, World!");
  for (let i = 0; i < 10; ++i) {
    const x = Math.pow(i, 4);
    window.alert("X is " + x);
  }
  switch (42) {
    case 8:
      let meow = (() => { throw new Error("lol"); /* lol */ })(); // meow
      break;
  }
}
    </code>

    <code class="syntax_vscode" language="python">

def foo():
  print("HEllo, WoRld!")
  for i in range(20):
    raise Exception("Meow")
  if cond:
    raise MyException("Woof")
  return "waffles"

class Meow:
  def __init__(self):
    super().__init__()

  @abstractmethod
  def weeeeee(self):
    return -3.14159

  def blarg(self):
    for i in range(1, 10):
      print(str(i) + " Mississippi")

    </code>


    <header><bookmark>Topic 1</bookmark></header>

    Lorem ipsum dolor sit amet


    <header><bookmark>Topic 2</bookmark></header>

        <enablebackticks/>

    <p>
    Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet
    Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet
    Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet
    Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet
    `This is some code` `and here is some ``inline`` code inline with ``backticks```
    Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet
    Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet
    Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet
    Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet
    </p>

    <div>
    <image mouseover="totoro">https://twocansandstring.com/uploads/drawn/4712.png</image>
    </div>

    <p>14</p>
    ';

    $rendered_content = (new HtmlPlusPlusParser($content))->parse();


?><html>
    <head>
        <title>HTML++ Test Page</title>
        <link rel="stylesheet" type="text/css" href="syntax_styling.css"/>
        <link rel="stylesheet" type="text/css" href="htmlplusplus.css"/>
        <style type="text/css">
body {
    font-family: "Free Sans", sans-serif;
}
        </style>
    </head>

    <body>
        <?php echo $rendered_content; ?>
    </body>
</html>