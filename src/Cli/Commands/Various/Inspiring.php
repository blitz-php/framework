<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Various;

use BlitzPHP\Cli\Console\Command;

/**
 * Une liste de citations d'inspiration pour vous aider dans votre productivite
 */
class Inspiring extends Command
{
    /**
     * @var string Groupe
     */
    protected $group = 'Various';

    /**
     * @var string Nom
     */
    protected $name = 'inspire';

    /**
     * @var string Description
     */
    protected $description = 'Citations d\'inspiration pour BlitzPHP.';

    /**
     * @var string Usage
     */
    protected $usage = 'php klinge inspire';

    /**
     * @var string
     */
    protected $service = 'Service de divertissement';

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $this->task('Cette citation pourrait vous motiver');

        $this->writer->boldGreen(static::quote(), true);
    }

    /**
     * Recupere une bonne citation d'inspiration.
     *
     * @see https://github.com/notepad-plus-plus/notepad-plus-plus/blob/246c8bd1684f89d1e3c87a77148bc51e6555f83c/PowerEditor/src/Notepad_plus.cpp#L5500
     * @see https://github.com/gmsantos/inspiring.git
     *
     * @credit https://github.com/agungsugiarto/codeigniter4-inspiring
     *
     * @return string Quote
     */
    public static function quote(): string
    {
        $quote = [
            "Good programmers use Notepad++ to code.\nExtreme programmers use MS Word to code, in Comic Sans, center aligned. - Notepad++",
            'Always code as if the guy who ends up maintaining your code will be a violent psychopath who knows where you live. - Martin Golding',
            'To iterate is human, to recurse divine. - L. Peter Deutsch',
            "The trouble with programmers is that you can never tell what a programmer is doing until it's too late. - Seymour Cray",
            'Debugging is twice as hard as writing the code in the first place. Therefore, if you write the code as cleverly as possible, you are, by definition, not smart enough to debug it. - Brian Kernighan',
            'Most software today is very much like an Egyptian pyramid with millions of bricks piled on top of each other, with no structural integrity, but just done by brute force and thousands of slaves. - Alan Kay',
            'Measuring programming progress by lines of code is like measuring aircraft building progress by weight. - Bill Gates',
            "Sometimes it pays to stay in bed on Monday, rather than spending the rest of the week debugging Monday's code. - Christopher Thompson",
            "I don't care if it works on your machine! We are not shipping your machine! - Vidiu Platon",
            'Walking on water and developing software from a specification are easy if both are frozen. - Edward V Berard',
            "Fine, Java MIGHT be a good example of what a programming language should be like. But Java applications are good examples of what applications SHOULDN'T be like. - pixadel",
            "I think Microsoft named .Net so it wouldn't show up in a Unix directory listing. - Oktal",
            "In C++ it's harder to shoot yourself in the foot, but when you do, you blow off your whole leg. - Bjarne Stroustrup",
            "Don't worry if it doesn't work right. If everything did, you'd be out of a job. - Mosher's Law of Software Engineering",
            'Writing in C or C++ is like running a chain saw with all the safety guards removed. - Bob Gray',
            'In the one and only true way. The object-oriented version of "Spaghetti code" is, of course, "Lasagna code". (Too many layers) - Roberto Waltman',
            'C++ : Where friends have access to your private members. - Gavin Russell Baker',
            "Software is like sex: It's better when it's free. - Linus Torvalds",
            'Emacs is a great operating system, lacking only a decent editor. - Cult of vi',
            'vi has two modes - "beep repeatedly" and "break everything". - Church of Emacs',
            "Picasso had a saying: \"Good artists copy, great artists steal.\".\nWe have always been shameless about stealing great ideas. - Steve Jobs",
            'Do everything for greatness, not money. Money follows greatness. - brotips #1001',
            'Cheating is like eating fast food: you do it, you enjoy it, and then you feel like shit. - brotips #1212',
            'God gave men both a penis and a brain, but unfortunately not enough blood supply to run both at the same time. - Robin Williams',
            "You don't get to 500 million star systems without making a few enemies. - Darth Vader",
            'A good programmer is someone who always looks both ways before crossing a one-way street. - Doug Linder',
            "A cookie has no soul, it's just a cookie. But before it was milk and eggs.\nAnd in eggs there's the potential for life. - Jean-Claude van Damme",
            'Java is, in many ways, C++--. - Michael Feldman',
            'Je mange donc je chie. - Don Ho',
            "RTFM is the true path of every developer.\nBut it would happen only if there's no way out. - Don Ho #2",
            'Smartphone is the best invention of 21st century for avoiding the eyes contact while crossing people you know on the street. - Don Ho #3',
            'Does your ass ever get jealous of all the shit that comes out of your month? - Anonymous #1',
            "Before sex, you help each other get naked, after sex you only dress yourself.\nMoral of the story: in life no one helps you once you're fucked. - Anonymous #2",
            "I'm not totally useless. I can be used as a bad example. - Anonymous #3",
            'Life is too short to remove USB safely. - Anonymous #4',
            "\"SEX\" is not the answer.\nSex is the question, \"YES\" is the answer. - Anonymous #5",
            "Going to Mc Donald's for a salad is like going to a whore for a hug. - Anonymous #6",
            'I need a six month holiday, TWICE A YEAR! - Anonymous #7',
            "Everything is a knife if you're strong enough. - Anonymous #8",
            "I just read a list of \"the 100 things to do before you die\". I'm pretty surprised \"yell for help\" wasn't one of them... - Anonymous #9",
            "Roses are red,\nViolets are red,\nTulips are red,\nBushes are red,\nTrees are red,\nHOLY SHIT MY\nGARDEN'S ON FIRE!! - Anonymous #10",
            'We stopped checking for monsters under our bed, when we realized they were inside us. - Anonymous #11',
            'I would rather check my facebook than face my checkbook. - Anonymous #12',
            'Whoever says Paper beats Rock is an idiot. Next time I see someone say that I will throw a rock at them while they hold up a sheet of paper. - Anonymous #13',
            'A better world is where chickens can cross the road without having their motives questioned. - Anonymous #14',
            "If I didn't drink, how would my friends know I love them at 2 AM? - Anonymous #15",
            "What you do after sex?\n  A. Smoke a cigarette\n  B. Kiss your partener\n  C. Clear browser history\n - Anonymous #16",
            "All you need is love,\nall you want is sex,\nall you have is porn.\n - Anonymous #17",
            'Never get into fights with ugly people, they have nothing to lose. - Anonymous #18',
            'F_CK: All I need is U. - Anonymous #19',
            'Never make eye contact while eating a banana. - Anonymous #20',
            'I love my sixpack so much, I protect it with a layer of fat. - Anonymous #21',
            "\"It's impossible.\" said pride.\n\"It's risky.\" said experience.\n\"It's pointless.\" said reason.\n\"Give it a try.\" whispered the heart.\n...\n\"What the hell was that?!?!?!?!?!\" shouted the anus two minutes later. - Anonymous #22",
            "Everybody talks about leaving a better planet for the children.\nWhy nobody tries to leave better children to the planet? - Anonymous #23",
            "An Architect's dream is an Engineer's nightmare. - Anonymous #24",
            "In a way, I feel sorry for the kids of this generation.\nThey'll have parents who know how to check browser history. - Anonymous #25",
            "I would never bungee jump.\nI came into this world because of a broken rubber, and I'm not going out cause of one. - Anonymous #26",
            "I don't have a problem with caffeine.\nI have a problem without caffeine. - Anonymous #27",
            "Why 6 afraid of 7?\nBecause 7 8 9 (seven ate nine) while 6 and 9 were flirting. - Anonymous #28",
            "Why do Java developers wear glasses?\nBecause they don't C#. - Anonymous #30",
            "A baby's laughter is one of the most beautiful sounds you will ever hear. Unless it's 3 AM. And you're home alone. And you don't have a baby. - Anonymous #31",
            "Two bytes meet. The first byte asks, \"You look terrible. Are you OK?\"\nThe second byte replies, \"No, just feeling a bit off.\" - Anonymous #32",
            'Programmer - an organism that turns coffee into software. - Anonymous #33',
            "It's not a bug - it's an undocumented feature. - Anonymous #34",
            "Should array index start at 0 or 1?\nMy compromised solution is 0.5 - Anonymous #35",
            "Every single time when I'm about to hug someone extremely sexy, I hit the mirror. - Anonymous #36",
            'My software never has bugs. It just develops random features. - Anonymous #37',
            'LISP = Lots of Irritating Silly Parentheses. - Anonymous #38',
            'Perl, the only language that looks the same before and after RSA encryption. - Anonymous #39',
            "People ask me why, as an atheist, I still say: OH MY GOD.\nIt makes perfect sense: We say \"Oh my God\" when something is UNBELIEVABLE. - Anonymous #40",
            "1. Dig a hole.\n2. Name it love.\n3. Watch people falling in love.\n - Anonymous #41",
            "Don't think of yourself as an ugly person.\nThink of yourself as a beautiful monkey. - Anonymous #42",
            "Afraid to die alone?\nBecome a bus driver. - Anonymous #43",
            'The first 5 days after the weekend are always the hardest. - Anonymous #44',
            'Rhinos are just fat unicorns. - Anonymous #45',
            "Today, I asked a girl out. She replied, \"Sorry, I'm suddenly a lesbian.\" FML - Anonymous #46",
            "Kids are like fart.\nYou can only stand yours. - Anonymous #47",
            "If you were born in Israel, you'd probably be Jewish.\nIf you were born in Saudi Arabia, you'd probably be Muslim.\nIf you were born in India, you'd probably be Hindu.\nBut because you were born in North America, you're Christian.\nYour faith is not inspired by some divine, constant truth.\nIt's simply geography. - Anonymous #48",
            "There are 2 types of people in this world:\nPeople who say they pee in the shower, and the dirty fucking liars. - Anonymous #49",
            "London 2012 Olympic Games - A bunch of countries coming across the ocean to put their flags in britain and try to get a bunch of gold... it's like history but opposite. - Anonymous #50",
            "I don't need a stable relationship,\nI just need a stable Internet connection. - Anonymous #51",
            "What's the difference between religion and bullshit?\nThe bull. - Anonymous #52",
            "Today, as I was waiting for my girlfriend in the street, I saw a woman who looked a lot like her. I ran towards her, my arms in the air ready to give her a hug, only to realise it wasn't her. I then had to pass the woman, my arms in the air, still running. FML - Anonymous #53",
            "Today, I finally got my hands on the new iPhone 5, after I pulled it out of a patient's rectum. FML - Anonymous #54",
            "Violent video games won't change our behaviour.\nIf people were influenced by video games, then the majority of Facebook users would be farmers right now. - Anonymous #55",
            "Religion is like circumcision.\nIf you wait until someone is 21 to tell them about it they probably won't be interested. - Anonymous #56",
            "No, no, no, I'm not insulting you.\nI'm describing you. - Anonymous #57",
            "I bought a dog once. Named him \"Stay\".\n\"Come here, Stay.\"\nHe's insane now. - Anonymous #58",
            "Yesterday I named my Wifi network \"hack me if you can\"\nToday when I woke up it was changed to \"challenge accepted\". - Anonymous #60",
            "Your mother is so fat,\nthe recursive function computing her mass causes a stack overflow. - Anonymous #61",
            'Oral sex makes my day, but anal sex makes my hole weak. - Anonymous #62',
            "I'm not saying I am Batman, I am just saying no one has ever seen me and Batman in the same room together. - Anonymous #63",
            "I took a taxi today.\nThe driver told me \"I love my job, I own this car, I've got my own business, I'm my own boss, NO ONE tells me what to do!\"\nI said \"TURN LEFT HERE\".\n - Anonymous #64",
            'A man without God is like a fish without a bicycle. - Anonymous #65',
            'I hate how spiders just sit there on the walls and act like they pay rent! - Anonymous #66',
            "Whenever someone starts a sentence by saying \"I'm not racist...\",they are about to say something super racist. - Anonymous #67",
            "I'm not laughing at you, I'm laughing with you, you're just not laughing. - Anonymous #68",
            'Women need a reason to have sex. Men just need a place. - Anonymous #69',
            'If abortion is murder then are condoms kidnapping? - Anonymous #70',
            "Men also have feelings.\nFor example, they can feel hungry. - Anonymous #71",
            "Project Manager:\nA person who thinks 9 women can deliver a baby in 1 month. - Anonymous #72",
            "If you try and don't succeed, cheat. Repeat until caught. Then lie. - Anonymous #73",
            "Olympics is the stupidest thing.\nPeople are so proud to be competing for their country.\nThey play their stupid song and raise some dumb flags.\nI'd love to see no flags raised, no song, no mention of country.\nOnly people. - Anonymous #74",
            "I think therefore I am\nnot religious. - Anonymous #75",
            "Even if being gay were a choice, so what?\nPeople choose to be assholes and they can get married. - Anonymous #76",
            "Governments are like diapers.\nThey should be changed often, and for the same reason. - Anonymous #77",
            "If you expect the world to be fair with you because you are fair, you're fooling yourself.\nThat's like expecting the lion not to eat you because you didn't eat him. - Anonymous #78",
            "I'm a creationist.\nI believe man create God. - Anonymous #79",
            "Let's eat kids.\nLet's eat, kids.\n\nUse a comma.\nSave lives. - Anonymous #80",
            "A male engineering student was crossing a road one day when a frog called out to him and said, \"If you kiss me, I'll turn into a beautiful princess.\" He bent over, picked up the frog, and put it in his pocket.\n\nThe frog spoke up again and said, \"If you kiss me and turn me back into a beautiful princess, I will stay with you for one week.\" The engineering student took the frog out of his pocket, smiled at it; and returned it to his pocket.\n\nThe frog then cried out, \"If you kiss me and turn me back into a princess, I'll stay with you and do ANYTHING you want.\" Again the boy took the frog out, smiled at it, and put it back into his pocket.\n\nFinally, the frog asked, \"What is the matter? I've told you I'm a beautiful princess, that I'll stay with you for a week and do anything you want. Why won't you kiss me?\" The boy said, \"Look I'm an engineer. I don't have time for a girlfriend, but a talking frog is cool.\"\n - Anonymous #81",
            "Programmers never die.\nThey just go offline. - Anonymous #82",
            "Copy from one, it's plagiarism.\nCopy from two, it's research. - Anonymous #83",
            'Saying that Java is nice because it works on all OSes is like saying that anal sex is nice because it works on all genders. - Anonymous #84',
            "Race, religion, ethnic pride and nationalism etc... does nothing but teach you how to hate people that you've never met. - Anonymous #85",
            'Farts are just the ghosts of the things we eat. - Anonymous #86',
            "I promised I would never kill someone who had my blood.\nBut that mosquito made me break my word. - Anonymous #87",
            "I'm drunk and you're still ugly. - Anonymous #89",
            "Clapping:\n(verb)\nRepeatedly high-fiving yourself for someone else's accomplishments. - Anonymous #90",
            'CV: ctrl-C, ctrl-V - Anonymous #91',
            "Mondays are not so bad.\nIt's your job that sucks. - Anonymous #92",
            "[In a job interview]\nInterviewer: What's your greatest weakness?\nCandidate: Honesty.\nInterviewer: I don't think honesty is a weakness.\nCandidate: I don't give a fuck what you think. - Anonymous #93",
            "Hey, I just met you\nAnd this is crazy\nHere's my number 127.0.0.1\nPing me maybe? - Anonymous #94",
            "YES!\nI'm a programmer, and\nNO!\nIt doesn't mean that I have to fix your PC! - Anonymous #95",
            'Code for 6 minutes, debug for 6 hours. - Anonymous #96',
            "Real Programmers don't comment their code.\nIf it was hard to write, it should be hard to read. - Anonymous #97",
            "My neighbours listen to good music.\nWhether they like it or not. - Anonymous #98",
            "I've been using Vim for about 2 years now,\nmostly because I can't figure out how to exit it. - Anonymous #99",
            "Dear YouTube,\nI can deal with Ads.\nI can deal with Buffer.\nBut when Ads buffer, I suffer. - Anonymous #100",
            "It's always sad when a man and his dick share only one brain...\nand it turns out to be the dick's. - Anonymous #101",
            "If IE is brave enough to ask you to set it as your default browser,\ndon't tell me you dare not ask a girl out. - Anonymous #102",
            'Turn on your brain, turn off TV. - Anonymous #103',
            "The main idea of \"Inception\":\nif you run a VM inside a VM inside a VM inside a VM inside a VM,\neverything will be very slow. - Anonymous #104",
            "When I die, I want to go peacefully like my grandfather did, in his sleep\n- not screaming, like the passengers in his car. - Anonymous #106",
            "Remember, YOUR God is real.\nAll those other Gods are ridiculous, made-up nonsense.\nBut not yours.\nYour God is real. Whichever one that is. - Anonymous #107",
            "I hope Bruce Willis dies of a Viagra overdose,\nThe way you can see the headline:\nBruce Willis, Died Hard - Anonymous #108",
            "A programmer had a problem, so he decided to use threads.\nNow 2 has. He problems. - Anonymous #110",
            "I love how the internet has improved people's grammar far more than any English teacher has.\nIf you write \"your\" instead of \"you're\" in English class, all you get is a red mark.\nMess up on the internet, and may God have mercy on your soul. - Anonymous #111",
            "#hulk \n    height: 200%;\n    width: 200%;\n    color: green;\n - Anonymous #112",
            "Open source is communism.\nAt least it is what communism was meant to be. - Anonymous #113",
            'How can you face your problem if your problem is your face? - Anonymous #114',
            "YOLOLO:\nYou Only LOL Once. - Anonymous #115",
            'Every exit is an entrance to new experiences. - Anonymous #116',
            "A Native American was asked:\n\"Do you celebrate Columbus day?\"\nHe replied:\n\"I don't know, do Jews celebrate Hitler's birthday?\" - Anonymous #117",
            "I love necrophilia, but i can't stand the awkward silences. - Anonymous #118",
            "\"I'm gonna Google that. BING that, Bing that, sorry.\"\n- The CEO of Bing (many times per day still) - Anonymous #119",
            "Life is what happens to you while you're looking at your smartphone. - Anonymous #120",
            "Thing to do today:\n1. Get up\n2. Go back to bed - Anonymous #121",
            "Nerd?\nI prefer the term \"Intellectual badass\". - Anonymous #122",
            'How can you face your problem if your problem is your face? - Anonymous #123',
            "You don't need religion to have morals.\nIf you can't determine right from wrong then you lack empathy, not religion. - Anonymous #124",
            'Pooping with the door opened is the meaning of true freedom. - Anonymous #125',
            "Social media does not make people stupid.\nIt just makes stupid people more visible. - Anonymous #126",
            "Don't give up your dreams.\nKeep sleeping. - Anonymous #127",
            "I love sleep.\nNot because I'm lazy.\nBut because my dreams are better than my real life. - Anonymous #128",
            "Common sense is so rare, it's kinda like a superpower... - Anonymous #130",
            'The best thing about a boolean is even if you are wrong, you are only off by a bit. - Anonymous #131',
            "Benchmarks don't lie, but liars do benchmarks. - Anonymous #132",
            'Multitasking: Screwing up several things at once. - Anonymous #133',
            "Linux is user friendly.\nIt's just picky about its friends. - Anonymous #134",
            "Theory is when you know something, but it doesn't work.\nPractice is when something works, but you don't know why.\nProgrammers combine theory and practice: nothing works and they don't know why. - Anonymous #135",
            "Documentation is like sex:\nwhen it's good, it's very, very good;\nwhen it's bad, it's better than nothing. - Anonymous #136",
            'Home is where you poop most comfortably. - Anonymous #137',
            'Laptop Speakers problem: too quiet for music, too loud for porn. - Anonymous #138',
            "Chinese food to go: \$16\nGas to go get the food: \$2\nDrove home just to realize they forgot one of your containers: RICELESS - Anonymous #139",
            'MS Windows is like religion to most people: they are born into it, accept it as default, never consider switching to another. - Anonymous #140',
            "To most religious people, the holy books are like a software license (EULA).\nNobody actually reads it. They just scroll to the bottom and click \"I agree\". - Anonymous #141",
            "You are nothing but a number of days,\nwhenever each day passes then part of you has gone. - Anonymous #142",
            'If 666 is evil, does that make 25.8069758011 the root of all evil? - Anonymous #143',
            "I don't want to sound like a badass but...\nI eject my USB drive without removing it safely. - Anonymous #144",
            "feet  (noun)\na device used for finding legos in the dark - Anonymous #145",
            "Buy a sheep\nName it \"Relation\"\nNow you have a Relationsheep\n - Anonymous #146",
            "I dig, you dig, we dig,\nhe dig, she dig, they dig...\n\nIt's not a beautiful poem,\nbut it's very deep. - Anonymous #147",
            "UNIX command line Russian roulette:\n[ \$[ \$RANDOM % 6 ] == 0 ] && rm -rf /* || echo *Click* - Anonymous #148",
            "unzip, strip, top, less, touch, finger, grep, mount, fsck, more, yes, fsck, fsck, fsck, umount, sleep.\n\nNo, it's not porn. It's Unix. - Anonymous #149",
            'To understand what recursion is, you must first understand recursion. - Anonymous #150',
            "Q: What's the object-oriented way to become wealthy?\nA: Inheritance. - Anonymous #151",
            'A SQL query goes into a bar, walks up to two tables and asks, "Can I join you?" - Anonymous #152',
            'You are not fat, you are just more visible. - Anonymous #153',
            "Minimalist\n (.   .)\n  )   (\n (  Y  )\nASCII Art - Anonymous #154",
            "I'm a good citizen. I'm a good father. I recycle and I masturbate. - Louis C.K.",
            "Someone I loved once gave me a box full of darkness.\nIt took me years to understand that this, too, was a gift. - Mary Oliver",
            'If you fall, I will be there. - Floor',
            "If you have some problem in your life and need to deal with it, then use religion, that's fine.\nI use Google. - Simon Amstell",
            'James, James Bond. - James Bond',
            "Only 3 things are infinite:\n1. Universe.\n2. Human Stupidity.\n3. Winrar's free trial. - Albert Einstein",
            'Artificial Intelligence is no match for natural stupidity. - Terry Pratchett',
            "Once a new technology starts rolling, if you're not part of the steamroller,\nyou're part of the road. - Stewart Brand",
            'Software and cathedrals are much the same - first we build them, then we pray. - Sam Redwine',
            'In theory, there is no difference between theory and practice. But, in practice, there is. - Jan L. A. van de Snepscheut',
            "One man's crappy software is another man's full time job. - Jessica Gaston",
            'Yes, we scan! - Barack Obama',
            "Where is my Nobel prize?\nI bombed people too. - George W. Bush",
            "Earth provides enough to satisfy every man's need, but not every man's greed. - Gandhi",
            'Life is a sexually transmitted disease and the mortality rate is one hundred percent. - R. D. Laing',
            "I'll buy a second iPhone 5 and buy a lot of iOS applications so that Apple will be able to buy Samsung (this shitty company)\nto shut it down and all the Apple haters will be forced to have an iPhone. Muhahaha... - Apple fan boy",
            "Politicians are like sperm.\nOne in a million turn out to be an actual human being. - Hustle Man",
            "Censorship is telling a man he can't have a steak just because a baby can't chew it. - Mark Twain",
            'There is not enough love and goodness in the world to permit giving any of it away to imaginary beings. - Friedrich Nietzsche',
            "Pain is a state of mind and I don't mind your pain. - Dhalsim",
            "Human beings can be beautiful or more beautiful,\nthey can be fat or skinny, they can be right or wrong,\nbut illegal? How can a human being be illegal? - Elie Wiesel",
            "Empty your memory, with a free(), like a pointer.\nIf you cast a pointer to a integer, it becomes the integer.\nIf you cast a pointer to a struct, it becomes the struct.\nThe pointer can crash, and can overflow.\nBe a pointer my friend. - Dennis Ritchie",
            "Uuuuuuuuuur Ahhhhrrrrrr\nUhrrrr Ahhhhrrrrrr\nAaaarhg... - Chewbacca",
            "Freedom of expression is like the air we breathe, we don't feel it, until people take it away from us.\n\nFor this reason, Je suis Charlie, not because I endorse everything they published, but because I cherish the right to speak out freely without risk even when it offends others.\nAnd no, you cannot just take someone's life for whatever he/she expressed.\n\nHence this \"Je suis Charlie\" edition.\n - #JeSuisCharlie",
            'I always wanted to be somebody, but now I realize I should have been more specific. - Lily Tomlin',
            'If at first you don’t succeed, then skydiving definitely isn’t for you. - Steven Wright',
            'I find television very educational. Every time someone turns it on, I go in the other room and read a book. - Groucho Marx',
            'Opportunity does not knock, it presents itself when you beat down the door. - Kyle Chandler',
            'Don’t worry about the world coming to an end today. It is already tomorrow in Australia. - Charles Schulz',
            'Age is of no importance unless you’re a cheese. - Billie Burke',
            'Never put off until tomorrow what you can do the day after tomorrow. - Mark Twain',
            'People often say that motivation doesn’t last. Well, neither does bathing; that’s why we recommend it daily. - Zig Ziglar',
            'The cleaner and nicer the program, the faster it’s going to run. And if it doesn’t, it’ll be easy to make it fast. - Joshua Bloch',
            'Think about it; and think about it carefully. Nothing happens in our society without software. Nothing. - Uncle Bob Martin',
            'Talk is cheap. Show me the code. - Linus Torvalds',
            "It's all talk until the code runs. - Ward Cunningha",
            'Good software, like wine, takes time. - Joel Spolsky',
        ];

        return $quote[array_rand($quote)];
    }
}
