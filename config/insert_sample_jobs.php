<?php
require_once 'config.php';
require_once 'database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Créer un recruteur si nécessaire
    $stmt = $conn->prepare("SELECT id FROM users WHERE user_type = 'recruiter' LIMIT 1");
    $stmt->execute();
    $recruiter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recruiter) {
        // Insérer un nouveau recruteur
        $stmt = $conn->prepare("INSERT INTO users (email, password, user_type, first_name, last_name) VALUES (?, ?, 'recruiter', ?, ?)");
        $stmt->execute(['recruteur@example.com', password_hash('password123', PASSWORD_DEFAULT), 'Jean', 'Dupont']);
        $recruiter_id = $conn->lastInsertId();

        // Créer le profil recruteur
        $stmt = $conn->prepare("INSERT INTO recruiter_profiles (user_id, company_name, company_description) VALUES (?, ?, ?)");
        $stmt->execute([$recruiter_id, 'Tech Innovations', 'Une entreprise leader dans le secteur technologique']);
    } else {
        $recruiter_id = $recruiter['id'];
    }

    // Liste des offres d'emploi à insérer
    $jobs = [
        [
            'title' => 'Développeur Full Stack React/Node.js',
            'company_name' => 'Tech Innovations',
            'location' => 'Paris',
            'type' => 'CDI',
            'description' => "Nous recherchons un développeur Full Stack passionné pour rejoindre notre équipe dynamique.\n\nVos responsabilités :\n- Développement de nouvelles fonctionnalités\n- Maintenance et amélioration des applications existantes\n- Participation aux choix techniques\n- Travail en méthode Agile",
            'requirements' => "- 3+ ans d'expérience en développement web\n- Maîtrise de React.js et Node.js\n- Expérience avec les bases de données SQL et NoSQL\n- Bon niveau en anglais\n- Esprit d'équipe et autonomie",
            'salary' => '45-55k€',
            'benefits' => "- RTT\n- Tickets restaurant\n- Mutuelle d'entreprise\n- Formation continue\n- Télétravail partiel",
            'status' => 'active'
        ],
        [
            'title' => 'Data Scientist Senior',
            'company_name' => 'Tech Innovations',
            'location' => 'Lyon',
            'type' => 'CDI',
            'description' => "Nous cherchons un Data Scientist expérimenté pour renforcer notre équipe Data.\n\nMissions :\n- Analyse de données complexes\n- Création de modèles prédictifs\n- Optimisation des algorithmes\n- Présentation des résultats aux parties prenantes",
            'requirements' => "- Master ou PhD en Data Science/Statistiques\n- 5+ ans d'expérience\n- Expertise en Python et R\n- Maîtrise de TensorFlow et PyTorch\n- Excellentes capacités de communication",
            'salary' => '60-75k€',
            'benefits' => "- Prime annuelle\n- Plan d'épargne entreprise\n- Horaires flexibles\n- Budget formation\n- Télétravail 3j/semaine",
            'status' => 'active'
        ],
        [
            'title' => 'Stage - Marketing Digital',
            'company_name' => 'Tech Innovations',
            'location' => 'Bordeaux',
            'type' => 'Stage',
            'description' => "Stage de 6 mois en Marketing Digital.\n\nVos missions :\n- Gestion des réseaux sociaux\n- Création de contenu digital\n- Analyse des performances marketing\n- Support aux campagnes publicitaires",
            'requirements' => "- En cours de formation Marketing/Communication\n- Maîtrise des outils digitaux\n- Créativité et autonomie\n- Première expérience en marketing digital appréciée",
            'salary' => '1000€/mois',
            'benefits' => "- Tickets restaurant\n- Transport pris en charge à 50%\n- Possibilité d'embauche",
            'status' => 'active'
        ],
        [
            'title' => 'Développeur Mobile Flutter',
            'company_name' => 'Tech Innovations',
            'location' => 'Nantes',
            'type' => 'CDD',
            'description' => "CDD de 12 mois pour le développement d'applications mobiles.\n\nPrincipales responsabilités :\n- Développement d'applications iOS/Android avec Flutter\n- Intégration d'APIs\n- Tests et débogage\n- Documentation technique",
            'requirements' => "- 2+ ans d'expérience avec Flutter\n- Connaissance de Dart\n- Expérience en développement mobile natif\n- Sensibilité UI/UX",
            'salary' => '40-45k€',
            'benefits' => "- Prime de fin de contrat\n- Mutuelle\n- Formation Flutter avancée",
            'status' => 'active'
        ],
        [
            'title' => 'Alternance - Développeur Web',
            'company_name' => 'Tech Innovations',
            'location' => 'Toulouse',
            'type' => 'Alternance',
            'description' => "Alternance de 12 à 24 mois en développement web.\n\nVous participerez à :\n- Développement de sites web\n- Intégration de maquettes\n- Maintenance d'applications\n- Tests unitaires",
            'requirements' => "- En formation Bac+3/5 en développement web\n- Connaissances en HTML, CSS, JavaScript\n- Motivation et curiosité\n- Esprit d'équipe",
            'salary' => 'Selon grille alternance',
            'benefits' => "- Tickets restaurant\n- Transport pris en charge\n- Accompagnement personnalisé",
            'status' => 'active'
        ],
        [
            'title' => 'DevOps Engineer',
            'company_name' => 'Tech Innovations',
            'location' => 'Paris',
            'type' => 'CDI',
            'description' => "Nous recherchons un DevOps Engineer pour optimiser notre infrastructure.\n\nMissions principales :\n- Gestion de l'infrastructure cloud\n- Mise en place de CI/CD\n- Monitoring et optimisation\n- Automatisation des déploiements",
            'requirements' => "- 4+ ans d'expérience en DevOps\n- Maîtrise de AWS/Azure\n- Expertise en Docker et Kubernetes\n- Scripting (Python, Bash)\n- Anglais courant",
            'salary' => '55-65k€',
            'benefits' => "- RTT\n- Participation\n- Intéressement\n- Formation continue\n- Télétravail flexible",
            'status' => 'active'
        ],
        [
            'title' => 'Product Owner',
            'company_name' => 'Tech Innovations',
            'location' => 'Lyon',
            'type' => 'CDI',
            'description' => "Nous recherchons un Product Owner expérimenté.\n\nVos responsabilités :\n- Gestion du backlog produit\n- Animation des cérémonies agiles\n- Coordination avec les parties prenantes\n- Définition de la roadmap",
            'requirements' => "- 3+ ans d'expérience en tant que PO\n- Certification Scrum\n- Expérience en développement produit\n- Excellente communication\n- Leadership",
            'salary' => '45-55k€',
            'benefits' => "- RTT\n- Mutuelle famille\n- Formation continue\n- Télétravail partiel",
            'status' => 'active'
        ],
        [
            'title' => 'UX/UI Designer',
            'company_name' => 'Tech Innovations',
            'location' => 'Bordeaux',
            'type' => 'CDI',
            'description' => "Recherche UX/UI Designer pour créer des expériences utilisateur exceptionnelles.\n\nMissions :\n- Création de wireframes et prototypes\n- Tests utilisateurs\n- Design d'interfaces\n- Collaboration avec les développeurs",
            'requirements' => "- 3+ ans d'expérience en UX/UI\n- Maîtrise de Figma et Adobe XD\n- Portfolio solide\n- Connaissance des principes de design\n- Créativité et innovation",
            'salary' => '40-50k€',
            'benefits' => "- RTT\n- Matériel de dernière génération\n- Formation UX/UI\n- Télétravail possible",
            'status' => 'active'
        ]
    ];

    // Insérer les offres
    $stmt = $conn->prepare("
        INSERT INTO jobs (
            recruiter_id, title, company_name, location, type,
            description, requirements, salary, benefits, status,
            created_at, expiry_date
        ) VALUES (
            :recruiter_id, :title, :company_name, :location, :type,
            :description, :requirements, :salary, :benefits, :status,
            NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)
        )
    ");

    foreach ($jobs as $job) {
        $job['recruiter_id'] = $recruiter_id;
        $stmt->execute($job);
    }

    echo "Les offres d'emploi ont été insérées avec succès !\n";

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
} 