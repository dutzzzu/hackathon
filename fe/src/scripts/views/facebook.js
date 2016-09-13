window.fbAsyncInit = function() {
    FB.init({
        appId      : '1060382770723822',
        xfbml      : true,
        version    : 'v2.7',
        status     : true
    });

    FB.Event.subscribe('auth.statusChange', function(response)
    {
        if (response.status === 'connected') {
            console.log("a intrat");
            var user;

            FB.api('/me', {
                fields: 'first_name,last_name,age_range,id,birthday,gender,hometown,name'
            }, function(response) {
                Application.userModel = new AppData._Models.User();
                Application.userModel.set({
                    id: response.id,
                    name: response.name,
                    age: response.age_range,
                    gender: response.gender,
                    birthday: response.birthday,
                    hometown: response.hometown
                });
                Application.userModel.save();

                if (Application.getCurrentPage().indexOf('wizard') !== 0) {
                    Application.navigate('wizard');
                }
            });
        }
    });

    window.fbApiInit = true; //init flag
};

(function(d, s, id){
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) {return;}
    js = d.createElement(s); js.id = id;
    js.src = "//connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

function checkFBStatus() {
    FB.getLoginStatus(function(response) {

        if (response.status === 'connected') {
            // the user is logged in and has authenticated your
            // app, and response.authResponse supplies
            // the user's ID, a valid access token, a signed
            // request, and the time the access token
            // and signed request each expire
            var uid = response.authResponse.userID;
            var accessToken = response.authResponse.accessToken;

        }
        else if (response.status === 'not_authorized') {
            // the user is logged in to Facebook,
            // but has not authenticated your app
        } else {
            // the user isn't logged in to Facebook.
        }
    });
}

