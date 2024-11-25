<?php
function isMobile() {
    return preg_match('/iphone|ipod|ipad|android|blackberry|opera mini|windows phone|webos|mobile/i', $_SERVER['HTTP_USER_AGENT']);
}

// Reindirizza a `index_telefono.php` se è un dispositivo mobile
if (isMobile()) {
    header('Location: index_telefono.php');
    exit;
}
// Avvia la sessione
session_start();

// Connessione al database 
//( se stai leggendo qua per trovare le key del database mi dispiace non ci riuscirai )
include('config.php');

// Crea la connessione
$conn = new mysqli($servername, $username, $password, $dbname);

// Controlla se la connessione è riuscita
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Ottieni la data odierna
$data_odierna = date('Y-m-d');

// Query per ottenere tutti i dati delle squadre (nome_squadra, punteggio, giorno, orario)
// Aggiungiamo l'ordinamento per giorno e orario in ordine decrescente
$sql = "SELECT nome_squadra, punteggio, giorno, orario FROM squadra ORDER BY giorno DESC, orario DESC"; // Ordina per giorno e orario decrescente
$result = $conn->query($sql);

// Verifica che ci siano risultati
if ($result->num_rows > 0) {
    // Raggruppamento delle squadre in base a giorno e orario
    $tornei = [];

    while ($row = $result->fetch_assoc()) {
        $giorno = $row['giorno'];
        $orario = $row['orario'];

        // Creazione di una chiave unica per giorno e orario
        $key = $giorno . " " . $orario;

        // Raggruppamento delle squadre sotto lo stesso giorno e orario
        if (!isset($tornei[$key])) {
            $tornei[$key] = [];
        }

        // Aggiunta della squadra al gruppo corrispondente
        $tornei[$key][] = $row;
    }
} else {
    $tornei = [];  // Imposta un array vuoto se non ci sono risultati
}
$sql_punti = "
    SELECT nome_squadra, SUM(punteggio) AS totale_punti
    FROM squadra
    GROUP BY nome_squadra
    ORDER BY totale_punti DESC
";
$result_punti = $conn->query($sql_punti);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizza Squadre e Tornei</title>
    <style>
        body {
            background-color: #1b1b1b;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .grid-container {
    display: grid;
    grid-template-columns: 2fr 1fr; /* Due colonne */
    grid-template-rows: auto auto; /* Due righe */
    gap: 20px; /* Spaziatura tra i rettangoli */
    width: 80%; /* Larghezza complessiva della griglia */
    margin: 80px 0; /* Margine superiore e inferiore */
}


        .grid-item {
            background-color: #2a2a2a;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        }

        .grid-item h3 {
            color: #68d391;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .grid-item p {
            color: #ffffff;
            font-size: 16px;
        }

        /* Tabella Tornei */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            color: #ffffff;
        }

        th, td {
            padding: 10px;
            text-align: center;
            border: 1px solid #3d3d3d;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        td {
            background-color: #2a2a2a;
        }

        .passato {
            background-color: #ff4d4d;
            color: #ff4d4d;
            font-weight: bold
        }

        .oggi {
            background-color: #ffcc00;
            color: #ffcc00;
            font-weight: bold
        }

        .futuro {
            background-color: #66cc66;
            color: #66cc66;
            font-weight: bold
        }

        .login-btn-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .login-btn {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .login-btn:hover {
            background-color: #45a049;
        }

        /* Legenda */
        .legenda {
            text-align: center;
            margin: 20px 20px;
            font-size: 16px;
            display: flex;
            justify-content: center;
            gap: 55px;
        }

        .legenda-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: bold;
        }

        .color-box {
            width: 20px;
            height: 20px;
            border-radius: 3px;
            display: inline-block;
        }

        .green {
            background-color: #66cc66; /* Verde */
        }

        .yellow {
            background-color: #ffcc00; /* Giallo */
        }

        .red {
            background-color: #ff4d4d; /* Rosso */
        }

        /* Box colorati per le posizioni */
/* Stile per le posizioni numeriche */
.primo-posto {
    color: #FFD700; /* Oro per il primo posto */
    font-weight: bold;
}

.secondo-posto {
    color: #C0C0C0; /* Argento per il secondo posto */
    font-weight: bold;
}

.terzo-posto {
    color: #CD7F32; /* Bronzo per il terzo posto */
    font-weight: bold;
}

.classifica-punti td {
    color: white; /* Colore bianco per il testo delle altre posizioni */
}


/* Stile di base per la classifica */
.classifica-punti table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.classifica-punti th, {
    padding: 10px;
    text-align: center;
    border: 1px solid #ddd;
}

.classifica-punti th {
    background-color: #333;
    color: white;
}

.classifica-punti td {
    background-color: #2a2a2a;
    color: white; /* Testo bianco per tutte le posizioni */
}
.primo-posto {
    color: #FFD700; /* Oro per il primo posto */
    font-weight: bold;
}

.secondo-posto {
    color: #C0C0C0; /* Argento per il secondo posto */
    font-weight: bold;
}

.terzo-posto {
    color: #CD7F32; /* Bronzo per il terzo posto */
    font-weight: bold;
}

/* Stile per la classifica */
.classifica-punti table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.classifica-punti th, .classifica-punti td {
    padding: 10px;
    text-align: center;
    border: 1px solid #ddd;
}

.classifica-punti th {
    background-color: #333;
    color: white;
}

.classifica-punti td {
    background-color: #2a2a2a;
    color: white; /* Testo bianco per tutte le posizioni */
}

.classifica-punti td span {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 50%;
}

.classifica-punti .primo-posto,
.classifica-punti .secondo-posto,
.classifica-punti .terzo-posto {
    font-weight: bold;
}
/* Contenitore login */
.grid-item:nth-child(2) {
    background-color: #5cb278; /* Colore verde scuro */
    position: relative;
    width: 80%; /* Occupare tutta la larghezza disponibile */
    max-width: 800px; /* Dimensione massima */
    align-self: center; /* Centra verticalmente */
    justify-self: end; /* Allinea a destra nella griglia */
    border-radius: 10px;
    word-wrap: break-word; /* Evita il traboccamento del testo */
}
@media (max-width: 768px) {
    .grid-container {
        grid-template-columns: 1fr; /* Una sola colonna su schermi stretti */
    }

    .grid-item:nth-child(2) {
        justify-self: center; /* Allinea al centro su schermi piccoli */
    }
}


.grid-item:nth-child(2) h3 {
    color: white; /* Colore del testo "Accedi" in bianco */
    margin-bottom: 15px;
    word-wrap: break-word; /* Evita il traboccamento del testo */
}

.grid-item:nth-child(2) p {
    color: white; /* Testo bianco per la descrizione */
    word-wrap: break-word; /* Evita il traboccamento del testo */
}

.grid-item:nth-child(2) img {
    width: 50px; /* Imposta una larghezza per l'immagine */
    height: auto; /* Mantiene le proporzioni originali dell'immagine */
    margin-top: 15px; /* Distanza tra il contenuto e l'immagine */
}

.grid-item:nth-child(2):hover {
    cursor: pointer; /* Mostra il cursore come "puntatore" quando si passa sopra il rettangolo */
}

.grid-item:nth-child(2) a {
    color: inherit; /* Mantiene il colore del testo */
    text-decoration: none; /* Rimuove la sottolineatura del link */
}
/* Rimuovi l'overflow dalla pagina */
html, body {
    overflow-x: hidden; /* Blocca il contenuto fuori dallo schermo */
}
/* Stile della scrollbar */
::-webkit-scrollbar {
    width: 12px; /* Larghezza della barra */
    height: 12px; /* Altezza della barra orizzontale */
    background: #2a2a2a; /* Colore della traccia per simulare il margine */
}

/* Colore della "traccia" (background della scrollbar) */
::-webkit-scrollbar-track {
    background: #1b1b1b; /* Colore dello sfondo per creare un effetto di margine */
    border: 2px solid #2a2a2a; /* Aggiunge un "bordo" per il margine */
    border-radius: 10px; /* Angoli arrotondati */
}

/* Colore e stile della "thumb" (la parte mobile) */
::-webkit-scrollbar-thumb {
    background-color: #68d391; /* Verde */
    border-radius: 8px; /* Angoli arrotondati */
    border: 2px solid #1b1b1b; /* Distanza aggiuntiva per simulare il margine */
}

/* Colore e stile della thumb quando viene hoverata */
::-webkit-scrollbar-thumb:hover {
    background-color: #5cb278; /* Verde più scuro quando hoverato */
}

/* Per Firefox */
scrollbar-color: #68d391 #1b1b1b; /* Thumb verde, track scura */
scrollbar-width: thin; /* Scrollbar sottile */


        
</style>
</head>
    <body>
        <div class="grid-container">
            <!-- Regolamento Torneo -->
            <div class="grid-item">
                <h3>REGOLAMENTO</h3>
                <p>Torneo uficiale IIS GALILEI - ARTIGLIO 2024-2025.</p>
                <p>Responsabile torneo: Prof. SMS Arrighi Marco</p> 
            </div>


            <div class="grid-item">
                <a href="login.php">
                    <img src="img/user-login.png" alt="Icona o immagine">
                    <h3>Sei un docente di Scienze Motorie Sportive?</h3>
                    <h2>Accedi come Admin</h2>
                </a>
            </div>


            <!-- Tornei -->
            <div class="grid-item">
                <h3>Tornei</h3>
                <p>Programmazione delle partite.</p>
                <p>I colori indicano lo stato delle partite, non il risultato delle squadre. </p>


                <!-- Legenda -->
                <div class="legenda">
                    <div class="legenda-item">
                        <span class="color-box green"></span> Partite in Programma
                    </div>
                    <div class="legenda-item">
                        <span class="color-box yellow"></span> Partite in Programma Oggi
                    </div>
                    <div class="legenda-item">
                        <span class="color-box red"></span> Partite Concluse
                    </div>
                </div>

                <!-- Tabella per i tornei -->
                <table>
                    <thead>
                        <tr>
                            <th>Giorno</th>
                            <th>Orario</th>
                            <th>Squadre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (count($tornei) > 0) {
                            foreach ($tornei as $key => $squadre) {
                                list($giorno, $orario) = explode(" ", $key);
                                $data_partita = $giorno;
                                $classe = '';

                                if ($data_partita < $data_odierna) {
                                    $classe = 'passato';
                                } elseif ($data_partita == $data_odierna) {
                                    $classe = 'oggi';
                                } elseif ($data_partita > $data_odierna) {
                                    $classe = 'futuro';
                                }

                                echo "<tr class='{$classe}'>";
                                echo "<td>" . htmlspecialchars($giorno) . "</td>";
                                echo "<td>" . htmlspecialchars($orario) . "</td>";

                                $squadre_list = [];
                                foreach ($squadre as $squadra) {
                                    $squadre_list[] = htmlspecialchars($squadra['nome_squadra']);
                                }

                                echo "<td>" . implode(" vs ", $squadre_list) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3'>Nessun torneo trovato.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Classifica Punti -->
            <div class="grid-item">
                <h3>Punti</h3>
                    <p>La classifica delle squadre è basata sui punti accumulati nei tornei.</p>
                    <p>In caso di parità, la squadra con il punteggio maggiore ottenuto per prima sarà posizionata  più in alto in classifica.</p>
                <table>
                    <thead>
                        <tr>
                            <th>Posizione</th>
                            <th>Squadra</th>
                            <th>Punteggio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $position = 1;  // Inizializza la variabile della posizione
                        // Verifica se ci sono risultati nella query
                        if ($result_punti->num_rows > 0) {
                            while ($row = $result_punti->fetch_assoc()) {
                                echo "<tr>";
                                
                                // Assegna la posizione corretta con la classe
                                if ($position == 1) {
                                    echo "<td><span class='primo-posto'>1</span></td>";  // Oro per il primo posto
                                } elseif ($position == 2) {
                                    echo "<td><span class='secondo-posto'>2</span></td>";  // Argento per il secondo posto
                                } elseif ($position == 3) {
                                    echo "<td><span class='terzo-posto'>3</span></td>";  // Bronzo per il terzo posto
                                } else {
                                    echo "<td>$position</td>";  // Per tutte le altre posizioni, mostra solo il numero
                                }

                                // Mostra il nome della squadra e il punteggio
                                echo "<td>" . htmlspecialchars($row['nome_squadra']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['totale_punti']) . "</td>";
                                echo "</tr>";
                                
                                $position++;  // Incrementa la posizione
                            }
                        } else {
                            echo "<tr><td colspan='3'>Nessuna squadra trovata.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </body>
</html>
