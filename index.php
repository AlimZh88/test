<?php  
session_start();   
?>                              
									 <?php include "__CSS.css"; 
  ?>
    
                                      
          <!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RNCheck.ru — Аксессуары для мобильных устройств</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <div class="container">
            <div class="logo">RN<span>Check.ru</span></div>
            <nav>
                <a href="#catalog">Каталог</a>
                <a href="#checkout-section">Оформить заказ (<span id="cart-count">0</span>)</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1>Аксессуары для мобильных устройств</h1>
        
        <div class="filter-buttons">
            <button class="filter-btn active" data-category="all">Все товары</button>
            <button class="filter-btn" data-category="Чехлы">Чехлы</button>
            <button class="filter-btn" data-category="Стекла">Стекла</button>
        </div>

        <div id="catalog" class="products-grid">
            <div class="loader">Загрузка аксессуаров...</div>
        </div>

        <!-- Секция оформления заказа и корзины -->
        <section id="checkout-section" class="checkout-container">
            <h2>Ваш заказ</h2>
            <div id="cart-items">Корзина пуста. Добавьте товары из каталога выше.</div>
            <div class="cart-total">Итого: <span id="cart-total-price">0</span> ₽</div>

            <form id="order-form" class="order-form">
                <h3>Контактные данные</h3>
                <input type="text" id="user-name" placeholder="Ваше имя">
                <input type="tel" id="user-phone" placeholder="Номер телефона *" required>
                <input type="email" id="user-email" placeholder="Ваш Email">
                <button type="submit" id="submit-order-btn" disabled>Отправить заказ</button>
            </form>
        </section>
    </main>

    <script>
        // Глобальное хранилище корзины в памяти страницы
        let cart = [];

        async function loadProducts(category = 'all') {
            const catalogContainer = document.getElementById('catalog');
            catalogContainer.innerHTML = '<div class="loader">Обновление каталога...</div>';
            
            try {
                const response = await fetch(`api.php?category=${encodeURIComponent(category)}`);
                if (!response.ok) throw new Error('Ошибка сервера');
                
                const products = await response.json();
                catalogContainer.innerHTML = '';
                
                if (products.length === 0) {
                    catalogContainer.innerHTML = '<div class="error">Товары отсутствуют.</div>';
                    return;
                }
                
                products.forEach(product => {
                    const card = document.createElement('div');
                    card.className = `product-card ${!product.available ? 'disabled' : ''}`;
                    
                    // Превращаем объект товара в строку, чтобы передать в функцию корзины
                    const productData = JSON.stringify(product).replace(/"/g, '&quot;');
                    
                    card.innerHTML = `
                        <img src="${product.image || 'https://placeholder.com'}" alt="${product.name}">
                        <div class="category">${product.category}</div>
                        <h3>${product.name}</h3>
                        <div class="price">${product.price} ₽</div>
                        <button ${!product.available ? 'disabled' : ''} onclick="addToCart(${productData})">
                            ${product.available ? 'В корзину' : 'Нет в наличии'}
                        </button>
                    `;
                    catalogContainer.appendChild(card);
                });
                
            } catch (error) {
                console.error('Ошибка:', error);
                catalogContainer.innerHTML = '<div class="error">Ошибка загрузки данных.</div>';
            }
        }

        // Функция добавления товара в корзину
        function addToCart(product) {
            const existingItem = cart.find(item => item.id === product.id);
            if (existingItem) {
                existingItem.count += 1;
            } else {
                cart.push({ id: product.id, name: product.name, price: product.price, count: 1 });
            }
            updateCartUI();
        }

        // Обновление интерфейса корзины
        function updateCartUI() {
            const cartItemsContainer = document.getElementById('cart-items');
            const cartCount = document.getElementById('cart-count');
            const cartTotalPrice = document.getElementById('cart-total-price');
            const submitBtn = document.getElementById('submit-order-btn');

            let totalCount = 0;
            let totalPrice = 0;

            if (cart.length === 0) {
                cartItemsContainer.innerHTML = 'Корзина пуста. Добавьте товары из каталога выше.';
                submitBtn.disabled = true;
            } else {
                cartItemsContainer.innerHTML = '';
                cart.forEach((item, index) => {
                    totalCount += item.count;
                    totalPrice += item.price * item.count;

                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'cart-item';
                    itemDiv.innerHTML = `
                        <span>${item.name} (${item.count} шт.)</span>
                        <span>${item.price * item.count} ₽</span>
                    `;
                    cartItemsContainer.appendChild(itemDiv);
                });
                submitBtn.disabled = false;
            }

            cartCount.textContent = totalCount;
            cartTotalPrice.textContent = totalPrice;
        }

        // Асинхронная отправка заказа на email через send_email.php 
        document.getElementById('order-form').addEventListener('submit', async (e) => {
            e.preventDefault(); // Предотвращаем перезагрузку страницы

            const orderData = {
                name: document.getElementById('user-name').value,
                phone: document.getElementById('user-phone').value,
                email: document.getElementById('user-email').value,
                cart: cart
            };

            try {
                const response = await fetch('send_email.php ', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(orderData)
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    cart = []; // Очищаем корзину после успеха
                    updateCartUI();
                    document.getElementById('order-form').reset();
                } else {
                    alert('Ошибка: ' + result.message);
                }
            } catch (error) {
                console.error('Ошибка отправки заказа:', error);
                alert('Не удалось отправить заказ. Попробуйте еще раз.');
            }
        });

        // Инициализация кликов по фильтрам
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                e.target.classList.add('active');
                loadProducts(e.target.getAttribute('data-category'));
            });
        });

        document.addEventListener('DOMContentLoaded', () => loadProducts('all'));
    </script>
</body>
</html>



