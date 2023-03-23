# draugiemgroup_fuel

Šis ir pabeigts fuel darbs pēc Draugiem Group prasībām.

Taisīts ar PHP, nedaudz JS, CSS un HTML
Taisīju to ar XAMPP. PHP 8.1.2.

Datubāzes:

users.sql => datubāze, kas satur visus lietotājus. Paroles ir hashotas ar SHA-256 un default useris kas ir datubāzē ir "abc:abcabc"

uploads.sql => datubāze, kas satur visu uploadoto transaction failu pathus. Tie arī satur "owner_id" fieldu, kas norāda uz tā user id, kurš uploadoja šo failu.

Mapīte "auth" satur login un reģistrācijas pages.
Mapīte "uploads" satur uploadotos failus.
Mapīte "user" satur failus, kuri ir useriem priekš funkcionalitātes. upload.php ir priekš file uploadošanas un procesošanas, index.php ir priekš galvenās izvēlnes un view.php ir lapa, kur tiek veiktas filtrēšanas un sakārtošanas.
Pārējie faili ir vienkārši visai mājaslapai kā atbalsts.

Savukārt mapīte "Pictures" ir priekš bildēm no mājaslapas. Tas ir lai jūs zinātu kā mājaslapa izskatās no mana skatu punkta. Es esmu uz Linux OS, un neesmu pārliecināts, ka uz citām OS tā izskatīsies tieši tā pat un vai viss būs vietā, tā, kā tam ir jābūt. Man diemžēl nav dual boot un nevaru pārbaudīt. 

Grūtības sagādāja un vairākas reizes pārrakstiju noteiktus algoritmus, jo vēlāk izdomāju kā labāk izdarīt (domāju labāk kad atnāku mājās no skolas).
Bet jau no sākuma zināju, ka tikšu galā. Šis bija grūtākais darbs ko es jebkad esmu programējis.
Es zinu, ka aplikācija varbūt nav tā pati drošākā, bet es pacentos tik cik zinu.

Atceraties, ka esmu gatavs mācīties jaunas lietas un uzlabot sadaļas kuras pieklibo.
Esmu daudz stundas pavadījis uz šo darbu un ceru ka tas jūs apmierinās.
