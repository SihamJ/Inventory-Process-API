<!-- HTML static page sending a text from a form as a JSON -->
<html>
<head>
    <title>Test</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script>
        function sendText(){
            var text = $("#text").val();
            $.ajax({
                url: "./query.php",
                type: "POST",
                data: text,
                dataType: "json",
                complete: function(response){
                    $("#response").html(response.responseText);
                }
            });
        }
    </script>
</head>
<body>
<textarea id="text" type="text"></textarea>
<button onclick="sendText()">Send</button>
<p id="response"></p>
</body>
</html>