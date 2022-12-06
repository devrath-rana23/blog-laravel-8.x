<?php

namespace App\Http\Controllers;

use App\Services\Newsletter;
use Exception;
use Illuminate\Validation\ValidationException;

class NewsletterController extends Controller
{
    //her we didn't write new Newsletter but things still worked. This is due to automatic dependency resolution.
    //SERVICE CONTAINER -> TOY CHEST
    //ItCH out of
    // In case of this Newslettter dependency laravel starts checking that toy chest or LARAVEL SERVICE CONTAINER
    // LARAVEL will check DO WE HAVE A NEWSLETTER
    // LARAVEL digs in and finds if we PUT anything like NEWSLETTER IN TOY CHEST answer is NO
    // LARAVEL then says OK so maybe we can MAGICALLY WHIP ONE UP FOR YOU
    // FIRST IT CHECKS WHAT NEWSLETTER IS AND FINDS NO CONSTRUCTOR DEPENDENCIES soit thinks preparing this object is as simple as instancing this object new Newsletter()
    //THAT's PRECISELY WHAT LARAVEL DOES
    // WHAT ABOUT THE CASES WHERE DEPENDENCIES DOES HAVE A CONSTRUCTOR
    // THEN IN THAT CASE LARAVEL READS AGAIN THAT NEWSLETTER CLASS WITH CONTRUCTOR DEFINED IN ITE TO PREPARE THE NEWSLETTER BUT ALSO ITS DEPENDENCIES
    // SO WHAT ES LEVEL OF DEEP THAT IT NEEDS TO
    //so now what it can magically instantiate is new Newsletter(new DependencyInjectedForThisNewsletterAsArgumentsOfNewsletterConstructor)
    // HOW LARAVEL RESOLVE THOSE DEPENDENCIES WHICH REQUIRE A VALUE WHICH LARAVEL IS NOT AWARE OF LIKE $foo in case of NESPAPER CONSTRUCTOR __construct(protected ApiClient $client, protected string $foo) in this particular case LARAVEL FAILS
    //It gives BindingResolutionException and laravel could'nt resolve this unknown dependency $foo so make note of it.
    //Case where depencdency EXISTS in TOY CHEST-> SERVICE CONTAINER
    //HOW TO PUT SOMETHING IN OUR TOY CHEST answer is PROVIDER
    //EG: in AppServiceProvider when the app boots if we want to disable the mass assignment restrictions this will be a good place to do that
    //BUT RIGHT ABOVE boot in register THIS IS THE PLACE WHERE WE REGISTER THINGS OR SERVICES IN OUR LARAVEL SERVICE CONTAINER or PUT A TOY IN OUR TOY CHEST LARAVEL DOES IT ALL OVER THIS PLACE
    /*
    public function register(){
        app()->bind('foo', function(){
            return 'bar';
        });
    }
    //foo is the KEY or some identifier and second argument is the closure which return something
    //here we refer app as our dependency
    //Now we can fetch it out of the container like this => app()->get('foo'); OR resolve('foo');
    //SO NOW WE KNOW WHY WE CALL IT CONTAINER, as we STORE IN MANY CASES A KEY VALUE PAIR.

    //SO USING THIS WE CAN RESOLVE THE ABOVE CASE FOR LARAVEL BY REGISTERING NEWSLETTER DEPENDENCY IN OUR CONTAINER AND DEFINE EXACTLY HOW WE WILL RESOLVE THAT UNRESOLVED DEPENDENCY BY LARAVEL $foo IN ABOVE CASE


    */

    //RESOLVING UNRESOLVED DEPENDENCY $foo CASE WITH BINDING EXCEPTION BY USING LARAVEL SERVICE CONTAINER and registering in APPSERVICEPROVIDER AS BELOW

    /*
        public function register(){
            app()->bind(Newsletter::class, function(){
                //Here we can be explicit about how to instantiate that newsletter unresolved dependency
                return new Newsletter(
                    new ApiClient(),
                    'foobar'
                );
            });
        }
        ABOVE IS THE EXAMPLE OF PUTTING SOMETHNG INTO THE TOY CHESTE CONTAINER

        SO now AGAIN LARAVEL CHECKS Newsletter dependency in our toy chest and find it.
        Now it will resolve it and pass it to us in the container and we would not need to instantiate that explicitly
        //Now we will not get this binding exception error
    */

    ///CONTRACTS

    // WHAT TO DO IF WE NEED TO PROVIDE MULTIPLE NEWSLETTER SERVICES LIKE IN THIS CASE MAILCHIMP IN ANOTHER CASE TICKERTUCK
    //SO WE NEED TO MAKE NEWSLETTER DEPENDENCY MORE FLEXIBLE
    //WE NOW WILL HAVE TWO SEPARATE SERVICES ONE FOR MAILCHIMP AND ONE FOR CONVERTKIT NEWSLETTER
    //WE WANT TO MAKE SURE that THEY BOTH CAN BE USED INTERCHANGABLY. THEY BOTH CONFORM TO THE SAME INTERFACE OR CONTRACT
    //WRITE NOW WE HAVE A SIMPLE CONTRACT subscribe() but in real case we may have multile methods
    //We can do it in two ways
    //keep it simple like we have subscribe contract or method in MAILCHIMP service so we can make the same name method in our ConvertKitNews service OR OTHER METHOD
    //WE CAN BE EXPLICIT ABOUT THAT CONTRACT
    //WE DO THAT BY CREATING A PHP INTERFACE
    //Name should be generic and since mailchimp and convertkit both are newsletter service so we can name our interface as Newsletter.
    //PHP INTERFACE
    //A php interface allows us to define a contract -> subscribe() method in this case. that any implementers of this interface must conform to. In This case MailChimp Service and ConvertKit Newsletter services both will implement Newsletter Interface SO they Both should have subscribe() method or contract defined inside them.

    //SO WE MAKE CONTRACTS USING INTERFACE

    // so we make subscribe contract using interface for mailchimp version which consumes mailchimp APIs and ConvertKit version which consumes ConvertKit APIs

    //Now in our newsletter controller we can now typehint our $newsletter object with any one of two services i.e. MailChimpNewsletter like below but this will be overkill
    /*
        public function __invoke(MailChimpNewsletter $newsletter){

        }
    */

    //But now our newsletter controller does not care which services are being used as long as you give him that method subscribe() from whichever service which conforms to Newsletter Interface.

    //so now if we write
    /*
 public function __invoke(Newsletter $newsletter){

        }

        it will fail as we cannot instantiate our interface Newsletter directly.
    */

    //So now if we go to the place wher we registered Newsletter Mailchimp in our container which is register() of AppServiceProvider.
    //here we are still return MailChimp service but Why dont we bind That with key as our Newsletter Interface instead of MailChimp class itself.
    //So now we have thrown a toy in our toy chest with name Newsletter which is actually our interface with subscribe contract() defined in it. which return a closure with particular MailChimp implementation or service of that Newsletter interface. Here we can write business logice to decide which version MailChimp or ConvertKit Newsletter service we want to use or return.
    //So now if we use Newsletter interface for instantiating and using newsletter service Mailchimp with subscribe method in our Newsletter controller this will not give error as we have explicitly resolved this case by registering in ou service container using AppServiceProvider register().

   //so we can now use MailChimp service thoroughout our application without explicitly instantiating it throughout our application but just registering it in our service container using AppServiceProvider register().

   //SO NOW ANYTIME WE WANT TO CHANGE OUR NEWSLETTER SERVICE TO CONVERTKIT WE NEED TO MAKE CHANGES TO ONLY ONE PLACE WHICH IS OUR SERVICEPROVIDER FILE AND WE ARE DONE.



    public function __invoke(Newsletter $newsletter)
    {
        request()->validate(['email' => 'required|email']);

        try {
            $newsletter->subscribe(request('email'));
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'email' => 'This email could not be added to our newsletter list.'
            ]);
        }

        return redirect('/')
            ->with('success', 'You are now signed up for our newsletter!');
    }
}
