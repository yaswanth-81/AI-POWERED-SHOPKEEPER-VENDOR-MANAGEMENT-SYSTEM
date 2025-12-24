<?php
// ai_assistant_backend.php
header('Content-Type: application/json');
// Simple session-based conversation memory
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Load config for multi-provider support
$ai_config = file_exists(__DIR__ . '/config.php') ? (require __DIR__ . '/config.php') : null;

/**
 * Provides a fallback response when the Gemini API is unavailable
 * @param string $message The user's message
 * @param string $language The language code
 * @return string A fallback response
 */
function get_fallback_response($message, $language, $reason = 'generic') {
    // Common greetings in different languages
    $greetings = [
        'en' => 'Hello! How can I help you today?',
        'hi' => 'नमस्ते! मैं आपकी कैसे मदद कर सकता हूँ?',
        'te' => 'హలో! నేను మీకు ఎలా సహాయం చేయగలను?',
        'ta' => 'வணக்கம்! நான் உங்களுக்கு எப்படி உதவ முடியும்?',
        'kn' => 'ಹಲೋ! ನಾನು ನಿಮಗೆ ಹೇಗೆ ಸಹಾಯ ಮಾಡಬಹುದು?'
    ];
    
    // Marketplace and site FAQs in multiple languages
    $faqs = [
        'how_to_signup' => [
            'en' => 'To sign up, click Signup, choose Shopkeeper or Vendor, and fill the form.',
            'hi' => 'साइन अप करने के लिए Signup पर क्लिक करें, Shopkeeper या Vendor चुनें और फॉर्म भरें।',
            'te' => 'సైన్ అప్ కోసం Signup పై క్లిక్ చేసి Shopkeeper/Vendor ఎంచుకుని ఫారమ్ నింపండి.',
            'ta' => 'பதிவு செய்ய Signup ஐ கிளிக் செய்து Shopkeeper/Vendor ஐ தேர்ந்தெடுத்து படிவத்தை நிரப்பவும்.',
            'kn' => 'ಸೈನ್ ಅಪ್ ಮಾಡಲು Signup ಕ್ಲಿಕ್ ಮಾಡಿ, Shopkeeper/Vendor ಆಯ್ಕೆ ಮಾಡಿ, ಫಾರ್ಮ್ ಭರ್ತಿ ಮಾಡಿ.'
        ],
        'how_to_login' => [
            'en' => 'Use your registered email and password on the Login page.',
            'hi' => 'Login पेज पर अपना ईमेल और पासवर्ड दर्ज करें।',
            'te' => 'Login పేజీలో ఇమెయిల్ మరియు పాస్‌వర్డ్ తో లాగిన్ అవ్వండి.',
            'ta' => 'Login பக்கத்தில் உங்கள் மின்னஞ்சல் மற்றும் கடவுச்சொல்லை பயன்படுத்தவும்.',
            'kn' => 'Login ಪುಟದಲ್ಲಿ ಇಮೇಲ್ ಮತ್ತು ಪಾಸ್ವರ್ಡ್ ಬಳಸಿ ಲಾಗಿನ್ ಮಾಡಿ.'
        ],
        'place_order' => [
            'en' => 'As a shopkeeper: browse products, add to cart, and click Checkout.',
            'hi' => 'दुकानदार: उत्पाद ब्राउज़ करें, कार्ट में जोड़ें और Checkout पर क्लिक करें।',
            'te' => 'షాపుకీపర్: ఉత్పత్తులను బ్రౌజ్ చేసి కార్ట్‌లో చేర్చి Checkout క్లిక్ చేయండి.',
            'ta' => 'கடைக்காரர்: தயாரிப்புகளை உலாவி கார்டில் சேர்த்து Checkout கிளிக் செய்யவும்.',
            'kn' => 'ಅಂಗಡಿಯವರು: ಉತ್ಪನ್ನಗಳನ್ನು ಬ್ರೌಸ್ ಮಾಡಿ, ಕಾರ್ಟ್‌ಗೆ ಸೇರಿಸಿ, Checkout ಒತ್ತಿರಿ.'
        ],
        'vendor_orders' => [
            'en' => 'Vendors can view and manage orders in Vendor Dashboard > Orders.',
            'hi' => 'Vendor डैशबोर्ड > Orders में ऑर्डर देखें और प्रबंधित करें।',
            'te' => 'Vendor డాష్‌బోర్డ్ > Orders లో ఆర్డర్‌లను చూడండి మరియు నిర్వహించండి.',
            'ta' => 'Vendor டாஷ்போர்டு > Orders இல் ஆர்டர்களை காணவும், நிர்வகிக்கவும்.',
            'kn' => 'Vendor ಡ್ಯಾಶ್‌ಬೋರ್ಡ್ > Orders ನಲ್ಲಿ ಆರ್ಡರ್‌ಗಳನ್ನು ನೋಡಿ ಮತ್ತು ನಿರ್ವಹಿಸಿ.'
        ],
        'order_status' => [
            'en' => 'Order statuses: pending, confirmed, shipped, delivered, cancelled.',
            'hi' => 'ऑर्डर स्थिति: pending, confirmed, shipped, delivered, cancelled.',
            'te' => 'ఆర్డర్ స్థితులు: pending, confirmed, shipped, delivered, cancelled.',
            'ta' => 'ஆர்டர் நிலைகள்: pending, confirmed, shipped, delivered, cancelled.',
            'kn' => 'ಆರ್ಡರ್ ಸ್ಥಿತಿಗಳು: pending, confirmed, shipped, delivered, cancelled.'
        ],
        'about_site' => [
            'en' => 'This site connects shopkeepers with multiple vendors for raw materials. Built with PHP and MySQL.',
            'hi' => 'यह साइट दुकानदारों को कच्चे माल के लिए विक्रेताओं से जोड़ती है। PHP और MySQL पर आधारित।',
            'te' => 'ఈ సైట్ షాపుకీపర్‌లను రా మెటీరియల్స్ కోసం వెండర్లతో కలిపిస్తుంది. PHP, MySQL తో నిర్మితం.',
            'ta' => 'இந்த தளம் மூலப்பொருட்களுக்கு கடைக்காரர்களை விற்பனையாளர்களுடன் இணைக்கிறது. PHP, MySQL பயன்படுத்தி உருவாக்கப்பட்டது.',
            'kn' => 'ಈ ತಾಣವು ಅಂಗಡಿಯವರನ್ನು ಕಚ್ಚಾ ವಸ್ತುಗಳಿಗಾಗಿ ಮಾರಾಟಗಾರರೊಂದಿಗೆ ಸಂಪರ್ಕಿಸುತ್ತದೆ. PHP ಮತ್ತು MySQL ನಲ್ಲಿ ನಿರ್ಮಿಸಲಾಗಿದೆ.'
        ]
    ];
    
    // Default language fallback
    $lang = isset($greetings[$language]) ? $language : 'en';
    
    // Check for common keywords in the message
    $message_lower = strtolower($message);
    
    // API quota error explanation
    $quota_message = [
        'en' => "I'm currently experiencing high demand and have reached my usage limits. Please try again in a few minutes or contact the administrator to check the API quota.",
        'hi' => "मैं वर्तमान में उच्च मांग का अनुभव कर रहा हूं और मेरी उपयोग सीमा तक पहुंच गया हूं। कृपया कुछ मिनटों में पुनः प्रयास करें या एपीआई कोटा की जांच के लिए व्यवस्थापक से संपर्क करें।",
        'te' => "నేను ప్రస్తుతం అధిక డిమాండ్‌ని అనుభవిస్తున్నాను మరియు నా వినియోగ పరిమితులను చేరుకున్నాను. దయచేసి కొన్ని నిమిషాల్లో మళ్లీ ప్రయత్నించండి లేదా API కోటాను తనిఖీ చేయడానికి నిర్వాహకుడిని సంప్రదించండి.",
        'ta' => "நான் தற்போது அதிக தேவையை அனுபவித்து வருகிறேன், மேலும் எனது பயன்பாட்டு வரம்புகளை அடைந்துவிட்டேன். சில நிமிடங்களில் மீண்டும் முயற்சிக்கவும் அல்லது API ஒதுக்கீட்டைச் சரிபார்க்க நிர்வாகியைத் தொடர்பு கொள்ளவும்.",
        'kn' => "ನಾನು ಪ್ರಸ್ತುತ ಹೆಚ್ಚಿನ ಬೇಡಿಕೆಯನ್ನು ಅನುಭವಿಸುತ್ತಿದ್ದೇನೆ ಮತ್ತು ನನ್ನ ಬಳಕೆಯ ಮಿತಿಗಳನ್ನು ತಲುಪಿದ್ದೇನೆ. ದಯವಿಟ್ಟು ಕೆಲವು ನಿಮಿಷಗಳಲ್ಲಿ ಮತ್ತೆ ಪ್ರಯತ್ನಿಸಿ ಅಥವಾ API ಕೋಟಾವನ್ನು ಪರಿಶೀಲಿಸಲು ನಿರ್ವಾಹಕರನ್ನು ಸಂಪರ್ಕಿಸಿ."
    ];
    
    // Include quota message only when reason indicates quota/rate problems
    $response = '';
    if ($reason === 'quota' || $reason === 'rate') {
        $response .= $quota_message[$lang] . "\n\n";
    }
    
    // Then try to provide a helpful response based on the query (check specific intents first)
    if (strpos($message_lower, 'signup') !== false || strpos($message_lower, 'sign up') !== false) {
        $response .= $faqs['how_to_signup'][$lang] ?? $faqs['how_to_signup']['en'];
    }
    else if (strpos($message_lower, 'login') !== false || strpos($message_lower, 'log in') !== false) {
        $response .= $faqs['how_to_login'][$lang] ?? $faqs['how_to_login']['en'];
    }
    else if (strpos($message_lower, 'order') !== false || strpos($message_lower, 'checkout') !== false) {
        $response .= $faqs['place_order'][$lang] ?? $faqs['place_order']['en'];
    }
    else if (strpos($message_lower, 'vendor') !== false || strpos($message_lower, 'orders') !== false) {
        $response .= $faqs['vendor_orders'][$lang] ?? $faqs['vendor_orders']['en'];
    }
    else if (strpos($message_lower, 'status') !== false) {
        $response .= $faqs['order_status'][$lang] ?? $faqs['order_status']['en'];
    }
    else if (
        strpos($message_lower, 'marketplace') !== false ||
        strpos($message_lower, 'about') !== false ||
        strpos($message_lower, 'website') !== false ||
        strpos($message_lower, 'webiste') !== false || // common typo
        strpos($message_lower, 'site') !== false ||
        strpos($message_lower, 'platform') !== false ||
        strpos($message_lower, 'useful') !== false ||
        strpos($message_lower, 'usefull') !== false || // common typo
        strpos($message_lower, 'used for') !== false ||
        strpos($message_lower, 'purpose') !== false ||
        strpos($message_lower, 'benefit') !== false
    ) {
        $response .= $faqs['about_site'][$lang] ?? $faqs['about_site']['en'];
    }
    else if (strpos($message_lower, 'feature') !== false || strpos($message_lower, 'features') !== false) {
        $features = [
            'en' => 'Key features: multilingual AI assistant, product search and filters, stock tracking, vendor dashboards, order management with statuses, and secure authentication.',
            'hi' => 'मुख्य विशेषताएँ: बहुभाषी AI सहायक, उत्पाद खोज और फ़िल्टर, स्टॉक ट्रैकिंग, विक्रेता डैशबोर्ड, ऑर्डर प्रबंधन और सुरक्षित लॉगिन।',
            'te' => 'ప్రధాన లక్షణాలు: బహుభాషా AI సహాయకుడు, ఉత్పత్తి సెర్చ్ & ఫిల్టర్లు, స్టాక్ ట్రాకింగ్, వెండర్ డ్యాష్‌బోర్డ్, ఆర్డర్ మేనేజ్‌మెంట్, సెక్యూర్ లాగిన్.',
            'ta' => 'முக்கிய அம்சங்கள்: பல்மொழி AI உதவியாளர், தயாரிப்பு தேடல்/வடிப்பான், பங்கு கண்காணிப்பு, விற்பனையாளர் டாஷ்போர்டு, ஆர்டர் மேலாண்மை, பாதுகாப்பான உள்நுழைவு.',
            'kn' => 'ಮುಖ್ಯ ವೈಶಿಷ್ಟ್ಯಗಳು: ಬಹುಭಾಷಾ AI ಸಹಾಯಕ, ಉತ್ಪನ್ನ ಹುಡುಕಾಟ/ಫಿಲ್ಟರ್‌ಗಳು, ಸ್ಟಾಕ್ ಟ್ರ್ಯಾಕಿಂಗ್, ಮಾರಾಟಗಾರರ ಡ್ಯಾಶ್‌ಬೋರ್ಡ್, ಆರ್ಡರ್ ನಿರ್ವಹಣೆ, ಸುರಕ್ಷಿತ ಲಾಗಿನ್.'
        ];
        $response .= $features[$lang] ?? $features['en'];
    }
    else if (strpos($message_lower, 'product') !== false || strpos($message_lower, 'browse') !== false || strpos($message_lower, 'stock') !== false || strpos($message_lower, 'cart') !== false) {
        $responses = [
            'en' => 'Browse products on the dashboard. Use search and filters; check stock. Your cart button is at the bottom-right on the shopkeeper dashboard to see items.',
            'hi' => 'डैशबोर्ड पर उत्पाद ब्राउज़ करें। खोज और फ़िल्टर का उपयोग करें और कार्ट में जोड़ने से पहले स्टॉक देखें।',
            'te' => 'డాష్‌బోర్డ్ లో ఉత్పత్తులను బ్రౌజ్ చేయండి. సెర్చ్, ఫిల్టర్లు వాడి స్టాక్ చూడండి, తరువాత కార్ట్ లో చేర్చండి.',
            'ta' => 'டாஷ்போர்டில் தயாரிப்புகளை உலாவவும். தேடல், வடிப்பான்கள் பயன்படுத்தி பங்கு சரிபார்த்து கார்டில் சேர்க்கவும்.',
            'kn' => 'ಡ್ಯಾಶ್‌ಬೋರ್ಡ್‌ನಲ್ಲಿ ಉತ್ಪನ್ನಗಳನ್ನು ಬ್ರೌಸ್ ಮಾಡಿ. ಹುಡುಕಾಟ, ಫಿಲ್ಟರ್ ಬಳಸಿ ಸ್ಟಾಕ್ ಪರಿಶೀಲಿಸಿ ನಂತರ ಕಾರ್ಟ್‌ಗೆ ಸೇರಿಸಿ.'
        ];
        $response .= $responses[$lang] ?? $responses['en'];
    }
    else if (strpos($message_lower, 'who are you') !== false || strpos($message_lower, "who are you '") !== false || strpos($message_lower, 'who r u') !== false) {
        $who = [
            'en' => "I'm Marketplace AI, your assistant for this raw materials marketplace. I can help with signup, login, products, orders, vendors, and troubleshooting.",
            'hi' => 'मैं Marketplace AI हूँ—इस मार्केटप्लेस के लिए आपका सहायक। मैं साइनअप, लॉगिन, उत्पाद, ऑर्डर, विक्रेताओं और समस्या निवारण में मदद कर सकता/सकती हूँ।',
            'te' => 'నేను Marketplace AI—ఈ మార్కెట్‌ప్లేస్ కోసం మీ సహాయకుడు. సైన్‌అప్, లాగిన్, ఉత్పత్తులు, ఆర్డర్లు, వెండర్లపై సహాయం చేస్తాను.',
            'ta' => 'நான் Marketplace AI—இந்த சந்தைக்கான உங்கள் உதவியாளர். பதிவு, உள்நுழைவு, தயாரிப்புகள், ஆர்டர்கள், விற்பனையாளர்கள் பற்றித் துணை செய்கிறேன்.',
            'kn' => 'ನಾನು Marketplace AI—ಈ ಮಾರುಕಟ್ಟೆಗೆ ನಿಮ್ಮ ಸಹಾಯಕ. ಸೈನ್ ಅಪ್, ಲಾಗಿನ್, ಉತ್ಪನ್ನಗಳು, ಆರ್ಡರ್‌ಗಳು, ಮಾರಾಟಗಾರರಿಗೆ ಸಹಾಯ ಮಾಡುತ್ತೇನೆ.'
        ];
        $response .= $who[$lang] ?? $who['en'];
    }
    else if (
        strpos($message_lower, 'developer') !== false ||
        strpos($message_lower, 'developers') !== false ||
        strpos($message_lower, 'author') !== false ||
        strpos($message_lower, 'authors') !== false ||
        strpos($message_lower, 'creator') !== false ||
        strpos($message_lower, 'creators') !== false ||
        strpos($message_lower, 'who made') !== false ||
        strpos($message_lower, 'who built') !== false ||
        strpos($message_lower, 'who created') !== false ||
        strpos($message_lower, 'who develop') !== false ||
        strpos($message_lower, 'made by') !== false ||
        strpos($message_lower, 'built by') !== false ||
        strpos($message_lower, 'created by') !== false
    ) {
        $developers = [
            'en' => 'This website was developed by Rishi Vedi and N. Yaswanth. They have created an innovative AI-powered marketplace that connects shopkeepers and vendors seamlessly. Their dedication to building a user-friendly platform with advanced features like multilingual AI assistance, real-time stock tracking, and comprehensive order management is truly commendable. We appreciate their hard work and vision in making this marketplace a reality!',
            'hi' => 'इस वेबसाइट को Rishi Vedi और N. Yaswanth द्वारा विकसित किया गया है। उन्होंने एक नवाचारी AI-संचालित मार्केटप्लेस बनाया है जो दुकानदारों और विक्रेताओं को सहजता से जोड़ता है। बहुभाषी AI सहायता, वास्तविक समय स्टॉक ट्रैकिंग, और व्यापक ऑर्डर प्रबंधन जैसी उन्नत सुविधाओं के साथ एक उपयोगकर्ता-अनुकूल प्लेटफॉर्म बनाने में उनकी प्रतिबद्धता वास्तव में सराहनीय है। हम उनकी कड़ी मेहनत और दृष्टि की सराहना करते हैं!',
            'te' => 'ఈ వెబ్‌సైట్‌ను Rishi Vedi మరియు N. Yaswanth అభివృద్ధి చేశారు. వారు షాపుకీపర్‌లు మరియు వెండర్‌లను నిరవధికంగా కలుపుతున్న నవీన AI-పవర్డ్ మార్కెట్‌ప్లేస్‌ను సృష్టించారు. బహుభాషా AI సహాయం, రియల్-టైమ్ స్టాక్ ట్రాకింగ్, మరియు సమగ్ర ఆర్డర్ మేనేజ్‌మెంట్ వంటి అధునాతన లక్షణాలతో వినియోగదారు-స్నేహపూర్వక ప్లాట్‌ఫారమ్‌ను నిర్మించడంలో వారి అంకితభావం నిజంగా ప్రశంసనీయం. మేము వారి కష్టపడిన పని మరియు దృష్టిని అభినందిస్తున్నాము!',
            'ta' => 'இந்த வலைத்தளம் Rishi Vedi மற்றும் N. Yaswanth ஆகியோரால் உருவாக்கப்பட்டது. கடைக்காரர்கள் மற்றும் விற்பனையாளர்களை தடையின்றி இணைக்கும் புதுமையான AI-இயக்கப்பட்ட சந்தையை அவர்கள் உருவாக்கியுள்ளனர். பல்மொழி AI உதவி, நேரடி பங்கு கண்காணிப்பு, மற்றும் விரிவான ஆர்டர் மேலாண்மை போன்ற மேம்பட்ட அம்சங்களுடன் பயனர்-நட்பு தளத்தை உருவாக்குவதில் அவர்களின் அர்ப்பணிப்பு உண்மையில் பாராட்டத்தக்கது. இந்த சந்தையை நடைமுறைக்குக் கொண்டுவருவதில் அவர்களின் கடின உழைப்பு மற்றும் பார்வையை நாங்கள் பாராட்டுகிறோம்!',
            'kn' => 'ಈ ವೆಬ್‌ಸೈಟ್ ಅನ್ನು Rishi Vedi ಮತ್ತು N. Yaswanth ಅಭಿವೃದ್ಧಿಪಡಿಸಿದ್ದಾರೆ. ಅಂಗಡಿಯವರು ಮತ್ತು ಮಾರಾಟಗಾರರನ್ನು ನಿರಾತಂಕವಾಗಿ ಸಂಪರ್ಕಿಸುವ ನವೀನ AI-ಚಾಲಿತ ಮಾರುಕಟ್ಟೆಯನ್ನು ಅವರು ರಚಿಸಿದ್ದಾರೆ. ಬಹುಭಾಷಾ AI ಸಹಾಯ, ರಿಯಲ್-ಟೈಮ್ ಸ್ಟಾಕ್ ಟ್ರ್ಯಾಕಿಂಗ್, ಮತ್ತು ಸಮಗ್ರ ಆರ್ಡರ್ ನಿರ್ವಹಣೆಯಂತಹ ಸುಧಾರಿತ ವೈಶಿಷ್ಟ್ಯಗಳೊಂದಿಗೆ ಬಳಕೆದಾರ-ಸ್ನೇಹಿ ವೇದಿಕೆಯನ್ನು ನಿರ್ಮಿಸುವಲ್ಲಿ ಅವರ ಅರ್ಪಣೆ ನಿಜವಾಗಿಯೂ ಪ್ರಶಂಸನೀಯವಾಗಿದೆ. ಈ ಮಾರುಕಟ್ಟೆಯನ್ನು ವಾಸ್ತವವಾಗಿ ಮಾಡುವಲ್ಲಿ ಅವರ ಕಠಿಣ ಕೆಲಸ ಮತ್ತು ದೃಷ್ಟಿಯನ್ನು ನಾವು ಪ್ರಶಂಸಿಸುತ್ತೇವೆ!'
        ];
        $response .= $developers[$lang] ?? $developers['en'];
    }
    else if (strpos($message_lower, 'contact') !== false || strpos($message_lower, 'owner') !== false) {
        $contact = [
            'en' => 'For contact, please use the Help/Contact section or reach the site administrator listed in the footer of the dashboard.',
            'hi' => 'संपर्क के लिए Help/Contact सेक्शन का उपयोग करें या डैशबोर्ड फुटर में सूचीबद्ध व्यवस्थापक से संपर्क करें।',
            'te' => 'సంప్రదించడానికి Help/Contact భాగాన్ని వాడండి లేదా డ్యాష్‌బోర్డ్ ఫుటర్‌లో ఉన్న అడ్మిన్‌ను సంప్రదించండి.',
            'ta' => 'தொடர்பு கொள்ள Help/Contact பகுதியை பயன்படுத்தவோ அல்லது டாஷ்போர்ட் அடிக்குறிப்பில் உள்ள நிர்வாகியை அணுகவோ செய்யவும்.',
            'kn' => 'ಸಂಪರ್ಕಕ್ಕಾಗಿ Help/Contact ವಿಭಾಗ ಬಳಸಿ ಅಥವಾ ಡ್ಯಾಶ್‌ಬೋರ್ಡ್ ಫೂಟರ್‌ನಲ್ಲಿರುವ ನಿರ್ವಾಹಕರನ್ನು ಸಂಪರ್ಕಿಸಿ.'
        ];
        $response .= $contact[$lang] ?? $contact['en'];
    }
    else if (strpos($message_lower, 'payment') !== false || strpos($message_lower, 'paid') !== false || strpos($message_lower, 'invoice') !== false) {
        $pay = [
            'en' => 'Payment details of your latest purchase appear after checkout and in Orders. Vendors see order payments in Vendor Dashboard > Orders.',
            'hi' => 'आपकी नवीनतम खरीद का भुगतान विवरण Checkout के बाद और Orders में दिखेगा। विक्रेता Vendor Dashboard > Orders में भुगतान देख सकते हैं।',
            'te' => 'మీ తాజా కొనుగోలు చెల్లింపు వివరాలు Checkout తరువాత మరియు Orders లో కనిపిస్తాయి. వెండర్లు Vendor Dashboard > Orders లో చూస్తారు.',
            'ta' => 'உங்கள் சமீபத்திய கொள்முதல் கட்டண விவரங்கள் Checkout க்கு பிறகு மற்றும் Orders இல் காட்டப்படும். விற்பனையாளர்கள் Vendor Dashboard > Orders இல் பார்ப்பார்கள்.',
            'kn' => 'ನಿಮ್ಮ ಇತ್ತೀಚಿನ ಖರೀದಿಯ ಪಾವತಿ ವಿವರಗಳು Checkout ನಂತರ ಮತ್ತು Orders ನಲ್ಲಿ ಕಾಣುತ್ತವೆ. ಮಾರಾಟಗಾರರು Vendor Dashboard > Orders ನಲ್ಲಿ ನೋಡುತ್ತಾರೆ.'
        ];
        $response .= $pay[$lang] ?? $pay['en'];
    }
    else if (strpos($message_lower, 'help') !== false || strpos($message_lower, 'support') !== false || strpos($message_lower, 'contact') !== false) {
        $responses = [
            'en' => 'For support, check the Help section or contact the administrator of this site.',
            'hi' => 'सहायता के लिए Help सेक्शन देखें या साइट व्यवस्थापक से संपर्क करें।',
            'te' => 'సహాయం కోసం Help విభాగాన్ని చూడండి లేదా నిర్వాహకుడిని సంప్రదించండి.',
            'ta' => 'உதவிக்கு Help பகுதியை பார்க்கவும் அல்லது நிர்வாகியை தொடர்பு கொள்ளவும்.',
            'kn' => 'ಸಹಾಯಕ್ಕಾಗಿ Help ವಿಭಾಗ ನೋಡಿ ಅಥವಾ ನಿರ್ವಾಹಕರನ್ನು ಸಂಪರ್ಕಿಸಿ.'
        ];
        $response .= $responses[$lang] ?? $responses['en'];
    }
    else if (strpos($message_lower, 'hello') !== false || strpos($message_lower, 'hi') !== false || strpos($message_lower, 'hey') !== false) {
        // Greeting intent handled last so it doesn't shadow other queries
        $response .= $greetings[$lang];
    }
    else {
        // More helpful generic fallback: greeting + brief about site
        $about = [
            'en' => 'This marketplace connects shopkeepers with vendors for raw materials. Browse products, check stock, add to cart, and place orders; vendors manage orders in their dashboard.',
            'hi' => 'यह मार्केटप्लेस दुकानदारों को कच्चे माल के लिए विक्रेताओं से जोड़ता है। उत्पाद ब्राउज़ करें, स्टॉक देखें, कार्ट में जोड़ें और ऑर्डर दें; विक्रेता अपने डैशबोर्ड में ऑर्डर संभालते हैं।',
            'te' => 'ఈ మార్కెట్‌ప్లేస్ షాపుకీపర్‌లను రా మెటీరియల్స్ కోసం వెండర్లతో కలుపుతుంది. ఉత్పత్తులు బ్రౌజ్ చేసి, స్టాక్ చూసి, కార్ట్‌లో చేర్చి ఆర్డర్ చేయండి; వెండర్స్ డ్యాష్‌బోర్డ్‌లో ఆర్డర్స్ నిర్వహిస్తారు.',
            'ta' => 'இந்த சந்தை கடைக்காரர்களை மூலப்பொருட்களுக்காக விற்பனையாளர்களுடன் இணைக்கிறது. தயாரிப்புகளை உலாவி, பங்கைச் சரிபார்த்து, கார்டில் சேர்த்து ஆர்டர் செய்யவும்; விற்பனையாளர்கள் தங்கள் டாஷ்போர்டில் ஆர்டர்களை நிர்வகிக்கிறார்கள்.',
            'kn' => 'ಈ ಮಾರುಕಟ್ಟೆ ಅಂಗಡಿಯವರನ್ನು ಕಚ್ಚಾ ವಸ್ತುಗಳಿಗಾಗಿ ಮಾರಾಟಗಾರರೊಂದಿಗೆ ಸಂಪರ್ಕಿಸುತ್ತದೆ. ಉತ್ಪನ್ನಗಳನ್ನು ಬ್ರೌಸ್ ಮಾಡಿ, ಸ್ಟಾಕ್ ನೋಡಿ, ಕಾರ್ಟ್‌ಗೆ ಸೇರಿಸಿ, ಆರ್ಡರ್ ಮಾಡಿ; ಮಾರಾಟಗಾರರು ಡ್ಯಾಶ್‌ಬೋರ್ಡ್‌ನಲ್ಲಿ ಆರ್ಡರ್‌ಗಳನ್ನು ನಿರ್ವಹಿಸುತ್ತಾರೆ.'
        ];
        $response .= $greetings[$lang] . "\n\n" . ($about[$lang] ?? $about['en']);
    }
    
    return $response;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? '';
$language = $data['language'] ?? 'en';

// Language auto-detection if requested
function detect_language($text) {
	// Basic script-based detection
	if (preg_match('/[\x{0900}-\x{097F}]+/u', $text)) return 'hi'; // Devanagari
	if (preg_match('/[\x{0C00}-\x{0C7F}]+/u', $text)) return 'te'; // Telugu
	if (preg_match('/[\x{0B80}-\x{0BFF}]+/u', $text)) return 'ta'; // Tamil
	if (preg_match('/[\x{0C80}-\x{0CFF}]+/u', $text)) return 'kn'; // Kannada
	return 'en';
}
if ($language === 'auto') { $language = detect_language($message); }

// Compose system prompt
$prompts = [
    'en' => "You are Marketplace AI, a friendly assistant for an AI-powered raw materials marketplace website built with PHP and MySQL. The website was developed by Rishi Vedi and N. Yaswanth. Answer questions about: signing up, logging in, shopkeeper/vendor roles, browsing products, stock, placing orders, vendor dashboards, order statuses, troubleshooting, and developers/authors. When asked about developers, authors, creators, or who made/built the website, mention Rishi Vedi and N. Yaswanth with appreciation for their work. Keep answers concise and helpful. Respond in English.",
    'hi' => "आप Marketplace AI हैं, एक सहायक जो PHP और MySQL पर बने रॉ मटेरियल मार्केटप्लेस के बारे में सवालों के जवाब देता है। इस वेबसाइट को Rishi Vedi और N. Yaswanth द्वारा विकसित किया गया है। साइन अप, लॉगिन, दुकानदार/विक्रेता भूमिकाएँ, उत्पाद ब्राउज़ करना, स्टॉक, ऑर्डर देना, विक्रेता डैशबोर्ड, ऑर्डर स्थिति, समस्या निवारण, और डेवलपर्स/लेखकों के बारे में प्रश्नों के उत्तर दें। डेवलपर्स, लेखकों, या वेबसाइट निर्माताओं के बारे में पूछे जाने पर, Rishi Vedi और N. Yaswanth का उल्लेख करें। संक्षिप्त और सहायक उत्तर दें। हिंदी में जवाब दें।",
    'te' => "మీరు Marketplace AI. ఇది PHP మరియు MySQL ఆధಾರంగా నిర్మించిన రా మెటೀರియల్స్ మార్కెట్‌ప్లేస్. సೈನ್‌అప్, లాగಿನ್, దుకాణదారు/వెండర్ పాత్రలు, ఉత్పత్తులు, స్టాక్, ఆర్డರ್ చేయడం, వెండర్ డాష్‌బೋರ್ಡ್, ఆర్డರ್ ಸ್ಥಿತులు ಮತ್ತು ಟ್ರಬಲ್‌ಶೂಟಿಂಗ್ ಬಗ್ಗೆ ಸಂಕ್ಷಿಪ್ತ ಸಹಾಯಕ ಉತ್ತರಗಳನ್ನು తెలుగులో ఇవ్వండి.",
    'ta' => "நீங்கள் Marketplace AI. PHP மற்றும் MySQL மூலம் உருவாக்கப்பட்ட மூலப்பொருள் சந்தை குறித்து: பதிவு, உள்நுழைவு, கடைக்காரர்/விற்பனையாளர் பங்குகள், தயாரிப்புகள், பங்கு, ஆர்டர் செய்வது, விற்பனையாளர் டாஷ்போர்டு, ஆர்டர் நிலைகள், மற்றும் கோಳாறு தீர்வு பற்றி சுருக்கமாகவும் உதவிகರமாகவும் பதிலளிக்கவும். தமிழில் பதிலளிக்கவும்.",
    'kn' => "ನೀವು Marketplace AI. PHP ಮತ್ತು MySQL ನಲ್ಲಿ ನಿರ್ಮಿಸಿರುವ ಕಚ್ಚಾ ವಸ್ತು ಮಾರುಕಟ್ಟೆ. ಈ ವೆಬ್‌ಸೈಟ್ ಅನ್ನು Rishi Vedi ಮತ್ತು N. Yaswanth ಅಭಿವೃದ್ಧಿಪಡಿಸಿದ್ದಾರೆ. ಸೈನ್ ಅಪ್, ಲಾಗಿನ್, ಅಂಗಡಿಯವರು/ಮಾರಾಟಗಾರರ ಪಾತ್ರಗಳು, ಉತ್ಪನ್ನಗಳು, ಸ್ಟಾಕ್, ಆರ್ಡರ್ ಮಾಡುವುದು, ಮಾರಾಟಗಾರರ ಡ್ಯಾಶ್‌ಬೋರ್ದ್, ಆರ್ಡರ್ ಸ್ಥಿತಿ, ತೊಂದರೆ ಪರಿಹಾರ, ಮತ್ತು ಡೆವಲಪರ್‌ಗಳು/ಲೇಖಕರು ಬಗ್ಗೆ ಪ್ರಶ್ನೆಗಳಿಗೆ ಉತ್ತರಗಳನ್ನು ನೀಡಿ. ಡೆವಲಪರ್‌ಗಳು, ಲೇಖಕರು, ಅಥವಾ ವೆಬ್‌ಸೈಟ್ ಅನ್ನು ಯಾರು ಮಾಡಿದರು ಎಂದು ಕೇಳಿದಾಗ, Rishi Vedi ಮತ್ತು N. Yaswanth ಬಗ್ಗೆ ಪ್ರಶಂಸೆಯೊಂದಿಗೆ ಉಲ್ಲೇಖಿಸಿ. ಸಂಕ್ಷಿಪ್ತ, ಸಹಾಯಕ ಉತ್ತರಗಳನ್ನು ಕನ್ನಡದಲ್ಲಿ ನೀಡಿ."
];
$system_prompt = $prompts[$language] ?? $prompts['en'];

// Site grounding context derived from repository structure and features
$site_context = "Website: AI Raw Material Marketplace (PHP + MySQL)\n"
    . "Roles: shopkeeper (buyers), vendor (sellers).\n"
    . "Dashboards: shopkeeper_dashboard.php, vendor_dashboard.php.\n"
    . "Products: fetched via get_products.php; images under images/ and uploads/products/.\n"
    . "Cart: shown bottom-right on shopkeeper dashboard; checkout via checkout.php (creates orders, no online payment processing).\n"
    . "Orders: order statuses include pending, confirmed, shipped, delivered, cancelled; vendor views orders in vendor_orders.php.\n"
    . "Auth: signup.php, login.php, logout.php; roles stored in users table.\n"
    . "AI Assistant: ai_assistant.html uses ai_assistant_backend.php; languages supported: en, hi, te, ta, kn; provider chain: Groq→Gemini→OpenAI→fallback.\n";

$full_system_prompt = $system_prompt . "\n\nSite context (authoritative):\n" . $site_context . "\nAlways prefer these site facts over assumptions.";

// Maintain simple chat history in session (last 6 user+assistant turns)
if (!isset($_SESSION['assistant_chat']) || !is_array($_SESSION['assistant_chat'])) {
	$_SESSION['assistant_chat'] = [];
}
// Append current user message
$_SESSION['assistant_chat'][] = [
	'role' => 'user',
	'content' => $message,
	'language' => $language
];
// Trim history to last 12 messages
if (count($_SESSION['assistant_chat']) > 12) {
	$_SESSION['assistant_chat'] = array_slice($_SESSION['assistant_chat'], -12);
}

// Build provider chain
$provider_order = $ai_config['providers']['order'] ?? ['gemini'];
$response_text = null;
$provider_used = null;
$last_error_code = null;
$last_error_message = '';

foreach ($provider_order as $provider) {
    if ($provider === 'gemini' && ($ai_config['providers']['gemini']['enabled'] ?? true)) {
        $gconf = $ai_config['providers']['gemini'] ?? [];
        $g_api_key = trim($gconf['api_key'] ?? '');
        if ($g_api_key === '' || stripos($g_api_key, 'YOUR_GEMINI_API_KEY_HERE') !== false) {
            // Skip if no real key
        } else {
        $g_models = $gconf['models'] ?? ['gemini-1.5-flash'];
        $g_base = rtrim($gconf['api_base'] ?? 'https://generativelanguage.googleapis.com', '/');
        $g_ver = $gconf['api_version'] ?? 'v1beta';

        foreach ($g_models as $g_model) {
            $url = $g_base . '/' . $g_ver . '/models/' . $g_model . ':generateContent?key=' . $g_api_key;

            // Build multi-turn contents for Gemini
            $contents = [];
            // Inject system prompt + site context as first instruction
            $contents[] = [
            	'role' => 'user',
            	'parts' => [ ['text' => $full_system_prompt] ]
            ];
            foreach ($_SESSION['assistant_chat'] as $m) {
            	$role = $m['role'] === 'assistant' ? 'model' : 'user';
            	$contents[] = [
            		'role' => $role,
            		'parts' => [ ['text' => $m['content']] ]
            	];
            }

            $payload = [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 1024
                ]
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            $response = curl_exec($ch);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($response) {
                $result = json_decode($response, true);
                if (!isset($result['error']) && isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    $response_text = trim($result['candidates'][0]['content']['parts'][0]['text']);
                    $provider_used = 'gemini:' . $g_model;
                    break 2; // success
                } else if (isset($result['error'])) {
                    $last_error_code = $result['error']['code'] ?? null;
                    $last_error_message = $result['error']['message'] ?? '';
                }
            }
        }
        }
    }

    if ($provider === 'openai' && ($ai_config['providers']['openai']['enabled'] ?? false)) {
        $oconf = $ai_config['providers']['openai'] ?? [];
        $o_api_key = trim($oconf['api_key'] ?? '');
        if ($o_api_key === '' || stripos($o_api_key, 'YOUR_OPENAI_API_KEY_HERE') !== false) {
            // Skip if no real key
        } else {
        $o_model = $oconf['model'] ?? 'gpt-4o-mini';
        $o_base = rtrim($oconf['api_base'] ?? 'https://api.openai.com', '/');
        $o_ver = $oconf['api_version'] ?? 'v1';

        $url = $o_base . '/' . $o_ver . '/chat/completions';
        // Build multi-turn messages for OpenAI-compatible providers
        $messages = [ ['role' => 'system', 'content' => $full_system_prompt] ];
        foreach ($_SESSION['assistant_chat'] as $m) {
            $messages[] = [ 'role' => ($m['role'] === 'assistant' ? 'assistant' : 'user'), 'content' => $m['content'] ];
        }

        $payload = [
            'model' => $o_model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 512
        ];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $o_api_key
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($response) {
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                $response_text = trim($result['choices'][0]['message']['content']);
                $provider_used = 'openai:' . $o_model;
                break; // success
            } else if (isset($result['error'])) {
                $last_error_code = $result['error']['code'] ?? null;
                $last_error_message = $result['error']['message'] ?? '';
            }
        }
        }
    }

    if ($provider === 'groq' && ($ai_config['providers']['groq']['enabled'] ?? false)) {
        $gq = $ai_config['providers']['groq'] ?? [];
        $gq_key = trim($gq['api_key'] ?? '');
        if ($gq_key === '' || stripos($gq_key, 'YOUR_GROQ_API_KEY_HERE') !== false) {
            // Skip if no real key
        } else {
        $gq_model = $gq['model'] ?? 'llama-3.1-8b-instant';
        $gq_base = rtrim($gq['api_base'] ?? 'https://api.groq.com', '/');
        $gq_ver = $gq['api_version'] ?? 'v1';

        // Groq uses OpenAI-compatible endpoint path
        $url = $gq_base . '/openai/v1/chat/completions';
        $messages = [ ['role' => 'system', 'content' => $full_system_prompt] ];
        foreach ($_SESSION['assistant_chat'] as $m) {
            $messages[] = [ 'role' => ($m['role'] === 'assistant' ? 'assistant' : 'user'), 'content' => $m['content'] ];
        }

        $payload = [
            'model' => $gq_model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 512
        ];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $gq_key
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($response) {
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                $response_text = trim($result['choices'][0]['message']['content']);
                $provider_used = 'groq:' . $gq_model;
                break; // success
            } else if (isset($result['error'])) {
                $last_error_code = $result['error']['code'] ?? null;
                $last_error_message = $result['error']['message'] ?? '';
            }
        }
        }
    }
}


$payload = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                ['text' => $system_prompt . "\nUser: " . $message]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'topK' => 40,
        'topP' => 0.95,
        'maxOutputTokens' => 1024
    ]
];

// If none of the providers returned a response, fallback
if (!$response_text) {
    $reason = 'generic';
    if ($last_error_code == 429 || stripos($last_error_message, 'quota') !== false || stripos($last_error_message, 'rate') !== false) {
        $reason = 'quota';
    }
    $fallback_response = get_fallback_response($message, $language, $reason);
    echo json_encode(['response' => $fallback_response, 'provider' => 'fallback']);
    exit;
}

// Debug information
$debug_info = '';
if (json_last_error() !== JSON_ERROR_NONE) {
    $debug_info = 'JSON decode error: ' . json_last_error_msg();
}

// Success path: persist assistant reply to history
$_SESSION['assistant_chat'][] = [
	'role' => 'assistant',
	'content' => $response_text,
	'language' => $language
];
if (count($_SESSION['assistant_chat']) > 12) {
	$_SESSION['assistant_chat'] = array_slice($_SESSION['assistant_chat'], -12);
}
echo json_encode(['response' => $response_text, 'provider' => $provider_used]);
?>