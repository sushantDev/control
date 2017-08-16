<!doctype html>
<!--[if lte IE 9]>
<html class="lte-ie9" lang="en"> <![endif]-->
<!--[if gt IE 9]><!-->
<html lang="en"> <!--<![endif]-->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Remove Tap Highlight on Windows Phone IE -->
    <meta name="msapplication-tap-highlight" content="no"/>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" href="{{ config('website.favicon') }}"/>

    <title>Vps Status - {{ config('website.name') }}</title>

    <!--[if lte IE 9]>
    <script type="text/javascript" src="{{ asset('js/libs/utils/html5shiv.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/libs/utils/respond.min.js') }}"></script><![endif]-->

    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            font-weight: 300;
            line-height: 1.42em;
            color: #F1F1F1;
            background-image: linear-gradient(to left, #BDBBBE 0%, #9D9EA3 100%), radial-gradient(88% 271%, rgba(255, 255, 255, 0.25) 0%, rgba(254, 254, 254, 0.25) 1%, rgba(0, 0, 0, 0.25) 100%), radial-gradient(50% 100%, rgba(255, 255, 255, 0.30) 0%, rgba(0, 0, 0, 0.30) 100%);
            background-blend-mode: normal, lighten, soft-light;
        }

        h1 {
            font-size: 3em;
            font-weight: 300;
            line-height: 1em;
            text-align: center;
            color: #F1F1F1;
        }

        h2 {
            font-size: 1em;
            font-weight: 300;
            text-align: center;
            display: block;
            line-height: 1em;
            padding-bottom: 2em;
            color: red;
        }

        h2 a {
            font-weight: 700;
            text-transform: uppercase;
            color: red;
            text-decoration: none;
        }

        .blue {
            color: #F1F1F1;
        }

        .yellow {
            color: #FFF842;
        }

        .container th h1 {
            font-weight: bold;
            font-size: 1em;
            text-align: left;
            color: #F1F1F1;
        }

        .container td {
            font-weight: normal;
            font-size: 1em;
            -webkit-box-shadow: 0 2px 2px -2px #0E1119;
            -moz-box-shadow: 0 2px 2px -2px #0E1119;
            box-shadow: 0 2px 2px -2px #0E1119;
        }

        .container {
            text-align: left;
            overflow: hidden;
            width: 80%;
            margin: 0 auto;
            display: table;
            padding: 0 0 8em 0;
        }

        .container td, .container th {
            padding-bottom: 0.75%;
            padding-top: 0.75%;
            padding-left: 0.75%;
        }

        /* Background-color of the odd rows */
        .container tr:nth-child(odd) {
            background-color: #98939B;
        }

        /* Background-color of the even rows */
        .container tr:nth-child(even) {
            background-color: #98939B;
        }

        .container th {
            background-color: #98939B;
        }

        .container td:first-child {
            color: red;
        }

        .container tr:hover {
            background-color: #464A52;
            -webkit-box-shadow: 0 6px 6px -6px #0E1119;
            -moz-box-shadow: 0 6px 6px -6px #0E1119;
            box-shadow: 0 6px 6px -6px #0E1119;
        }

        .container td:hover {
            background-color: #FFF842;
            color: #403E10;
            font-weight: bold;
        }

        @media (max-width: 800px) {
            .container td:nth-child(4),
            .container th:nth-child(4) {
                display: none;
            }
        }

        .active {
            background-color: green !important
        }
        .inactive {
            background-color: red !important
        }
        .loading {
            background-color: grey !important
        }
    </style>
</head>
<body>
<div id="app">
    <h1>Vps Status</h1>
    <h2>Green for Active, Red for not Inactive</h2>
    <table class="container">
        <thead>
        <tr>
            <th><h1>Server ID</h1></th>
            <th><h1>Customer</h1></th>
            <th><h1>IP</h1></th>
            <th><h1>Should Be</h1></th>
            <th><h1>Status</h1></th>
        </tr>
        </thead>
        <tbody>
            <tr v-for="provision in vpsProvisions">
                <td>@{{ provision.server_id }}</td>
                <td>@{{ provision.customer.name }}</td>
                <td>@{{ provision.ip }}</td>
                <td :class="provision.is_suspended == 1 ? 'active' : 'inactive' ">@{{ provision.is_suspended == true ? "active" : "inactive" }}</td>
                <td :class="provision.status">
                    @{{ provision.status }}
                </td>
            </tr>
        </tbody>
    </table>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.16.2/axios.min.js"></script>
<script src="{{ asset('js/vue.js') }}"></script>
<script>
    const traffic = new Vue({
        el: '#app',
        data: {
            vpsProvisions: {!! $vpsProvisions->map(function($provision) { $provision['status'] = 'unknown'; return $provision; }) !!},
        },
        ready: function () {
            this.init();
            setInterval(() => this.init(), 5 * 60 * 1000);
        },
        methods: {
            init() {
                this.vpsProvisions.map((vps) => this.getPing(vps));
            },
            getPing (vps) {
                vps.status = 'loading';
                axios
                .post('/ping/', {
                    ip: vps.ip,
                    _token: '{{ csrf_token() }}'
                })
                .then(response => vps.status = response.data)
                .catch( error => console.log(error));
            }
        }
    });
</script>
</body>
</html>