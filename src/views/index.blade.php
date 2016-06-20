<?php
header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");
?>
        <!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Eventix API Docs</title>
    <link href='vendor/swaggervel/css/typography.css' media='screen' rel='stylesheet' type='text/css'/>
    <link href='vendor/swaggervel/css/reset.css' media='screen' rel='stylesheet' type='text/css'/>
    <link href='vendor/swaggervel/css/screen.css' media='screen' rel='stylesheet' type='text/css'/>
    <link href='vendor/swaggervel/css/reset.css' media='print' rel='stylesheet' type='text/css'/>
    <link href='vendor/swaggervel/css/print.css' media='print' rel='stylesheet' type='text/css'/>
    <script src='vendor/swaggervel/lib/jquery-1.8.0.min.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/lib/jquery.slideto.min.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/lib/jquery.wiggle.min.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/lib/jquery.ba-bbq.min.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/lib/handlebars-2.0.0.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/lib/underscore-min.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/lib/backbone-min.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/swagger-ui.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/lib/highlight.7.3.pack.js' type='text/javascript'></script>
    <script src='vendor/swaggervel/lib/marked.js' type='text/javascript'></script>

    <script src='vendor/swaggervel/lib/swagger-oauth.js' type='text/javascript'></script>

    <script type="text/javascript">

        var clientId = false;
        var clientSecret = false;

        function log() {
            if ('console' in window) {
                console.log.apply(console, arguments);
            }
        }

        $(function () {
            var url = window.location.search.match(/url=([^&]+)/);
            if (url && url.length > 1) {
                url = decodeURIComponent(url[1]);
            } else {
                url = "{!! $urlToDocs !!}";
            }

            // Pre load translate...
            if (window.SwaggerTranslator) {
                window.SwaggerTranslator.translate();
            }
            window.swaggerUi = new SwaggerUi({
                url: url,
                dom_id: "swagger-ui-container",
                supportedSubmitMethods: ['get', 'post', 'put', 'delete', 'patch'],
                onComplete: function (swaggerApi, swaggerUi) {

                    log("Loaded SwaggerUI");

                    $.get("client").done(function (data) {
                        var buttonHTML = "";
                        var selectHTML = "";

                        while(data.length){
                            var d = data.pop();

                            buttonHTML += "<div style='margin: 5px;'><button class='justClient'>"+ d.name + "</button></div>";
                            selectHTML += "<option>"+ d.name + "</option>";
                        }

                        document.getElementById("clientSelect").innerHTML += selectHTML;
                        document.getElementById("clientButtons").innerHTML = buttonHTML;

                    }).fail(function (data) {
                        console.log(data);
                        window.alert("Failed loading clients")
                    });

                    $.get("userList").done(function (data) {
                        var buttonHTML = "";
                        while(data.length){
                            var d = data.pop();

                            var name = d.name.split(" ")[0];
                            buttonHTML += "<div style='margin: 5px;'><button class='tokenMaker' disabled>"+ name + "</button></div>";

                        }

                        document.getElementById("userList").innerHTML = buttonHTML;

                    }).fail(function (data) {
                        console.log(data);
                        window.alert("Failed loading users")
                    });


                    $("#userList").on('click', '.tokenMaker', function () {
                        if (clientId === false || clientSecret === false) {
                            window.alert("Please select a client first.");
                            return;
                        }

                        var name = this.innerHTML;
                        var password = name;
                        var email = name.toLowerCase() + "@eventix.nl";
                        var from = ['a', 'e', 'i', 'o', 'h', 't'];
                        var to = ['@', '3', '1', '0', '4', '7'];

                        for (var i in from) {
                            password = password.replace(from[i], to[i]);
                        }

                        $.post("token", {
                            "grant_type": "password",
                            "client_id": clientId,
                            "client_secret": clientSecret,
                            "username": email,
                            "password": password
                        }, function (data) {
                            var el = $("#inputapiKey")[0];
                            el.value = (data.access_token);

                            document.getElementById("typeHelper").innerHTML = " - Logged in as <u>User</u>";

                            setAPIKey(el)
                        }).fail(function (data) {
                            window.alert('Could not log on as user: ' + name);
                        });
                    });
                    $("#clientButtons").on('click', 'button', function () {
                        var client = this.innerHTML;
                        $.get("client/" + client, function (data) {
                            if (!data.id && !data.secret) {
                                window.alert('Could not authenticate client: ' + client);
                                return;
                            }

                            $.post("token", {
                                "grant_type": "client_credentials",
                                "client_id": data.id,
                                "client_secret": data.secret,
                            }, function (data) {
                                var el = $("#inputapiKey")[0];
                                el.value = (data.access_token);
                                console.log(document.getElementById("header"));
                                document.getElementById("typeHelper").innerHTML = " - Logged in as <u>Client</u>";

                                setAPIKey(el)
                            });
                        }).fail(function (data) {
                            window.alert('Could not authenticate client: ' + client);
                        });
                    });

                    $("#clientSelect").change(function () {
                        $(".tokenMaker").each(function () {
                            this.setAttribute('disabled', 1);
                        });
//                        return;
                        var client = this.value;

                        $.get("client/" + client, function (data) {
                            if (!data.id && !data.secret) {
                                window.alert('Could not get client info for: ' + client);
                                return;
                            }

                            clientId = data.id;
                            clientSecret = data.secret;

                            $(".tokenMaker").each(function () {
                                this.removeAttribute('disabled');
                            });
                        }).fail(function (data) {
                            window.alert('Could not get client info for: ' + client);
                        });

                    });

                    if (window.SwaggerTranslator) {
                        window.SwaggerTranslator.translate();
                    }

                    $('pre code').each(function (i, e) {
                        hljs.highlightBlock(e)
                    });
                },
                onFailure: function (data) {
                    log("Unable to Load SwaggerUI");
                },
                docExpansion: "none",
                apisSorter: "alpha",
                showRequestHeaders: false
            });

            $('#init-oauth').click(function () {
                if (typeof initOAuth == "function") {
                    initOAuth({
                        clientId: $('#input_clientId').val() || "my-client-id",
                        clientSecret: $('#input_clientSecret').val() || "_",
                        realm: $('#input_realm').val() || "_",
                        appName: $('#input_appName').val() || "_",
                        scopeSeparator: "+"
                    });
                }
            });

            window.swaggerUi.load();

        });

        function setAPIKey(el) {
            if (!el)
                return;

            var value = el.value;

            if (value.length == 40) {
                var apiKeyAuth = new SwaggerClient.ApiKeyAuthorization("Authorization", value, "header");
                window.swaggerUi.api.clientAuthorizations.add("Authorization", apiKeyAuth);
            }
        }
    </script>
</head>

<body class="swagger-section">
<div id='header' style="position:fixed; left: 0px; right: 0px;">
    <div class="swagger-ui-wrap">
        <a id="logo" href="//eventix.io">Eventix API Docs <span id="typeHelper"></span></a>

        <form id='api_selector'>
            <div class='input'><input onkeyup="setAPIKey(this)" placeholder="Token" id="inputapiKey" name="apiKey"
                                      type="text"/></div>
        </form>
    </div>
</div>

<div id="message-bar" class="swagger-ui-wrap" data-sw-translate>&nbsp;</div>
<div id="swagger-ui-container" style="margin-top: 40px;" class="swagger-ui-wrap"></div>
</body>
</html>
