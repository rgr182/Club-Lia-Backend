<!DOCTYPE>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <!--[if !mso]><!-->
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <script type="text/javascript" src="{{ asset('js/login.js') }}"></script>
    <!--<![endif]-->
    <!--[if (gte mso 9)|(IE)]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
    <style type="text/css">

        body, html {
            height: 100%;
            margin: 0;
        }

        .main-image {
            background-image: url({{asset("/images/Home-Lia.jpg")}});
            background-repeat: no-repeat;
            background-size: cover;
            height: 85vh;
            width: 100%;
        }

    </style>
    <!--user entered Head Start-->

    <!--End Head user entered-->
</head>
<body>
    <div class="sticky-bar">
        <div class="sticky-bar-inner h-6">

            <div class="site-logo-container">
                <div class="site-logo">
                    <img class="lg-image" border="0"
                         src="{{asset('/images/clublia.png')}}"
                         alt="Off Grid Adventures" width="140"
                         data-responsive="true"
                         data-proportionally-constrained="false">
                </div>
            </div>

            <div id="search-panel" class="search-panel mr-7">
                <div class="js_temp_friend_search_form"></div>
                <form method="get" action="http://comunidad.test/index.php/search/" class="header_search_form" id="header_search_form">
                    <div class="form-group has-feedback">
                        <span class="ico ico-arrow-left btn-globalsearch-return"></span>
                        <div style="position: relative;" class="clear_input_div">
                            <input type="text" name="q" placeholder="Search..." autocomplete="off" class="form-control input-sm in_focus" id="header_sub_menu_search_input">
                            <a style="position: absolute; cursor: pointer; display: none;" class="clear_input">
                                <span class="ico ico-close"></span>
                            </a>
                        </div>
                        <span class="ico ico-search-o form-control-feedback" data-action="submit_search_form"></span>
                        <span class="ico ico-search-o form-control-feedback btn-mask-action"></span>
                    </div>
                </form>
            </div>


            <div class="guest-login-small" data-component="guest-actions">
                <a class="btn btn-sm btn-default popup" rel="hide_box_title visitor_form" role="link" href="http://comunidad.test/index.php/user/register/">
                    Sign Up    </a>
                <a class="btn btn-sm btn-success btn-gradient popup" rel="hide_box_title visitor_form" role="link" href="http://comunidad.test/index.php/login/">
                    Sign in    </a>
            </div>

        </div>
    </div>

    <div class="main-image">

        <div class="circle-img-container wh-container">
            <div class="banner">
                <img class="circle-img" border="0"
                     src="{{asset('/images/lia-style.png')}}"
                     alt="Off Grid Adventures"
                     data-responsive="true"
                     data-proportionally-constrained="false">
            </div>
        </div>

        <div class="signin-main-container wh-container">
        
            <div class="signin-container">
                <div>
                    <h1>Alumnos</h1>
                </div>
                <div class="tab">
                    <button class="tablinks second-link" onclick="openCity(event, 'up')">Sign In</button>
                    <button class="tablinks firts-link" onclick="openCity(event, 'in')">Sign Up</button>
                </div>

                <div id="up" class="tabcontent">
                    <input type="email" name="email" placeholder="email">
                    <input type="password" name="password" placeholder="Password">
                    <input type="checkbox"> Remember me
                    <input type="submit" value="Login" name="login">
                </div>

                <div id="in" class="tabcontent">
                    <form method="POST" action="">
                        <input type="text" placeholder="Full Name">
                        <input type="text" placeholder="Choose a Username">
                        <input type="email" name="email" placeholder="Email">
                        <input type="password" name="password" placeholder="Password">
                    </form>
                </div>
            </div>
        </div>

    </div>


</body>
</html>
