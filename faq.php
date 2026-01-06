<?php
require_once 'includes/config.php';

$page_title = "Frequently Asked Questions - TMM Academy";
include 'includes/header.php';

// Get FAQs from database
$conn = getDBConnection();
$sql = "SELECT * FROM faqs ORDER BY display_order, category, id";
$result = $conn->query($sql);
$faqs = [];
while ($faq = $result->fetch_assoc()) {
    $faqs[] = $faq;
}

// Group FAQs by category
$faqs_by_category = [];
foreach ($faqs as $faq) {
    $category = $faq['category'] ?: 'General';
    if (!isset($faqs_by_category[$category])) {
        $faqs_by_category[$category] = [];
    }
    $faqs_by_category[$category][] = $faq;
}
?>

<style>
        .faq-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .faq-header h1 {
            color: white;
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        .faq-header p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .faq-search {
            max-width: 600px;
            margin: -30px auto 50px;
            position: relative;
        }
        
        .faq-search input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: none;
            border-radius: 10px;
            box-shadow: var(--shadow-lg);
            font-size: 1rem;
        }
        
        .faq-search button {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 1.2rem;
            cursor: pointer;
        }
        
        .faq-categories {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        
        .faq-category-btn {
            padding: 10px 25px;
            background: var(--light-color);
            border: 2px solid var(--border-color);
            border-radius: 30px;
            color: var(--dark-color);
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .faq-category-btn:hover,
        .faq-category-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .faq-section {
            margin-bottom: 60px;
        }
        
        .faq-section-title {
            color: var(--primary-color);
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .faq-item {
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .faq-question {
            padding: 20px;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .faq-question:hover {
            background: var(--light-color);
        }
        
        .faq-question h3 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--primary-color);
            flex: 1;
        }
        
        .faq-toggle {
            color: var(--secondary-color);
            font-size: 1.2rem;
            transition: transform 0.3s;
        }
        
        .faq-answer {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s;
            background: var(--light-color);
        }
        
        .faq-answer.open {
            padding: 20px;
            max-height: 1000px;
        }
        
        .faq-answer p {
            margin: 0;
            line-height: 1.8;
            color: var(--dark-color);
        }
        
        .contact-cta {
            background: linear-gradient(135deg, var(--accent-color) 0%, #2ecc71 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
            margin-top: 60px;
            border-radius: 10px;
        }
        
        .contact-cta h2 {
            color: white;
            margin-bottom: 20px;
        }
        
        .contact-cta a {
            display: inline-block;
            background: white;
            color: var(--accent-color);
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: transform 0.3s;
        }
        
        .contact-cta a:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
    </style>
</head>
<body>

    <!-- FAQ Header -->
    <div class="faq-header">
        <div class="container">
            <h1>Frequently Asked Questions</h1>
            <p>Find quick answers to common questions about TMM Academy</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container" style="padding: 40px 0;">
        <!-- Search Box -->
        <div class="faq-search">
            <input type="text" id="faqSearch" placeholder="Search for questions...">
            <button type="button" onclick="searchFAQs()">
                <i class="fas fa-search"></i>
            </button>
        </div>

        <!-- FAQ Categories -->
        <div class="faq-categories">
            <button class="faq-category-btn active" onclick="showCategory('all')">All Questions</button>
            <?php foreach (array_keys($faqs_by_category) as $category): ?>
                <button class="faq-category-btn" onclick="showCategory('<?php echo strtolower($category); ?>')">
                    <?php echo $category; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- FAQ Sections -->
        <div id="faqContent">
            <?php foreach ($faqs_by_category as $category => $category_faqs): ?>
                <div class="faq-section" id="category-<?php echo strtolower($category); ?>">
                    <h2 class="faq-section-title">
                        <i class="fas fa-folder"></i> <?php echo $category; ?>
                    </h2>
                    
                    <?php foreach ($category_faqs as $index => $faq): ?>
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(<?php echo $faq['id']; ?>)">
                                <h3><?php echo htmlspecialchars($faq['question']); ?></h3>
                                <div class="faq-toggle">
                                    <i class="fas fa-chevron-down" id="toggle-icon-<?php echo $faq['id']; ?>"></i>
                                </div>
                            </div>
                            <div class="faq-answer" id="answer-<?php echo $faq['id']; ?>">
                                <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Contact CTA -->
        <div class="contact-cta">
            <div class="container">
                <h2>Still have questions?</h2>
                <p>Can't find what you're looking for? Our support team is here to help you.</p>
                <a href="mailto:support@tmmacademy.com">
                    <i class="fas fa-envelope"></i> Contact Support
                </a>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>

<script>
        // FAQ Toggle Function
        function toggleFAQ(faqId) {
            const answer = document.getElementById('answer-' + faqId);
            const icon = document.getElementById('toggle-icon-' + faqId);
            
            if (answer.classList.contains('open')) {
                answer.classList.remove('open');
                icon.className = 'fas fa-chevron-down';
            } else {
                answer.classList.add('open');
                icon.className = 'fas fa-chevron-up';
            }
        }
        
        // Show/Hide Categories
        function showCategory(category) {
            // Update active button
            document.querySelectorAll('.faq-category-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Show/hide sections
            const allSections = document.querySelectorAll('.faq-section');
            
            if (category === 'all') {
                allSections.forEach(section => {
                    section.style.display = 'block';
                });
            } else {
                allSections.forEach(section => {
                    if (section.id === 'category-' + category) {
                        section.style.display = 'block';
                    } else {
                        section.style.display = 'none';
                    }
                });
            }
        }
        
        // Search FAQs
        function searchFAQs() {
            const searchTerm = document.getElementById('faqSearch').value.toLowerCase();
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('h3').textContent.toLowerCase();
                const answer = item.querySelector('p').textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = 'block';
                    
                    // Highlight search term
                    if (searchTerm) {
                        const questionElement = item.querySelector('h3');
                        const answerElement = item.querySelector('p');
                        
                        // Simple highlighting (for demo)
                        questionElement.innerHTML = questionElement.textContent.replace(
                            new RegExp(searchTerm, 'gi'),
                            match => `<span style="background: yellow;">${match}</span>`
                        );
                        
                        answerElement.innerHTML = answerElement.textContent.replace(
                            new RegExp(searchTerm, 'gi'),
                            match => `<span style="background: yellow;">${match}</span>`
                        );
                    }
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Show category headers
            document.querySelectorAll('.faq-section').forEach(section => {
                const visibleItems = section.querySelectorAll('.faq-item[style="display: block"]');
                if (visibleItems.length > 0) {
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }
            });
        }
        
        // Auto-search on input
        document.getElementById('faqSearch').addEventListener('input', searchFAQs);
        
        // Open first FAQ by default
        document.addEventListener('DOMContentLoaded', function() {
            const firstFaq = document.querySelector('.faq-item');
            if (firstFaq) {
                const firstFaqId = firstFaq.querySelector('.faq-answer').id.replace('answer-', '');
                toggleFAQ(firstFaqId);
            }
        });
    </script>