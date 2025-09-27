<!-- Chatbot Modal -->
<div id="chatbot-modal" class="chatbot-modal">
    <div class="chatbot-header">
        <h4>AI Travel Assistant</h4>
        <button class="chatbot-close-btn" onclick="toggleChatbot()">&times;</button>
    </div>
    <div id="chatbot-messages" class="chatbot-messages">
        <div class="message-bubble message-ai">
            <p>Hello, how can I help you?</p>
        </div>
    </div>
    <form class="chatbot-input-container">
        <input type="text" id="user-input" class="chatbot-input" placeholder="Type your question..." />
        <button class="chatbot-send-btn" type="submit">
            <i class="fa fa-paper-plane"></i>
        </button>
    </form>
</div>

<!-- Modal Overlay -->
<div id="modal-overlay" class="modal-overlay" onclick="toggleChatbot()"></div>

<!--Chatbot End-->
<script src="assets/js/chatbot.js" defer></script>
<!--Chatbot Start-->

<div class="chatbot-widget-container">
    <!-- Chat Icon -->
    <div class="chatbot-icon" onclick="toggleChatbot()">
        <img src="./assets/images/chat-bot.png" alt="AI Assistant" class="chatbot-avatar" />
        <span class="chatbot-status-online"></span>
    </div>
</div>