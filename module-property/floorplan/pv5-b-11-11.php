<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f0f0;
        }

        .container {
            width: 450px;
            height: 500px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            grid-template-rows: 1fr 1fr 1fr 1fr 1fr;
            border: 1px solid black;
        }

        .room,
        .toilet,
        .kitchen {
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .room-r3,
        .room-r1 {
            display: initial;
            grid-template-columns: 1fr;
            grid-template-rows: 1fr;
            gap: 2px;
            padding-top: 40%;
        }

        .room-r2 {
            display: initial;
            grid-template-columns: 1fr;
            grid-template-rows: 1fr;
            gap: 2px;
            padding-top: 25%;
        }

        .room-r4 {
            display: initial;
            grid-template-columns: 1fr;
            grid-template-rows: 1fr;
            gap: 2px;
            padding-top: 10%;
        }

        .room-r3 div,
        .room-r4 div,
        .room-r1 div {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }

        .room-r2 div {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2px;
            top: 0;

        }

        .room-r3 {
            grid-column: 1 / span 1;
            grid-row: 1 / span 2;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }

        .toilet {
            grid-column: 1;
            grid-row: 3;
            width: 65%;
            border-right: 1px solid black;
        }

        .room-r4 {
            grid-column: 1;
            grid-row: 4;
            border-right: 1px solid black;
            border-top: 1px solid black;
        }

        .room-r1 {
            grid-column: 3;
            grid-row: 1 / span 3;
            border-left: 1px solid black;
            border-bottom: 1px solid black;
        }

        .room-r2 {
            grid-column: 3;
            grid-row: 4 / span 2;
            border-left: 1px solid black;
        }

        .kitchen {
            grid-column: 1;
            grid-row: 5;
            border-right: 1px solid black;
            border-top: 1px solid black;
        }

        button {
            width: 50%;
        }
    </style>
    <title>Room Layout</title>
</head>

<body>
    <div class="container">
        <div class="room room-r3">
            <div>T :&nbsp;<button>B10</button></div>
            <div>B :&nbsp;<button>B9</button></div>
        </div>
        <div class="toilet">TOILET</div>
        <div class="room room-r4">
            <div>T :&nbsp;<button>B12</button></div>
            <div>B :&nbsp;<button>B11</button></div>
        </div>
        <div class="room room-r1">
            <div>T :&nbsp;<button>B4</button></div>
            <div>B :&nbsp;<button>B3</button></div>
            <br>
            <div>T :&nbsp;<button>B2</button></div>
            <div>B :&nbsp;<button>B10</button></div>

        </div>
        <div class="room room-r2">
            <div>T :&nbsp;<button>B4</button></div>
            <div>B :&nbsp;<button>B3</button></div>
            <br>
            <div>T :&nbsp;<button>B2</button></div>
            <div>B :&nbsp;<button>B10</button></div>
        </div>
        <div class="kitchen">KITCHEN</div>
    </div>
</body>

</html>