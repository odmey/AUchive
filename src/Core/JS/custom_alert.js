(function () {
    // Add custom alert styles dynamically to the head
    const style = document.createElement('style');
    style.innerHTML = `
        .custom-alert-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999999;
            opacity: 0;
            transition: opacity 0.25s ease;
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box;
        }
        .custom-alert-box {
            background: #1a1a1a;
            border: 1px solid rgba(255, 244, 79, 0.25);
            border-radius: 18px;
            padding: 32px 24px;
            width: 90%;
            max-width: 380px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 20px rgba(255, 244, 79, 0.08);
            transform: scale(0.85) translateY(-15px);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-sizing: border-box;
        }
        .custom-alert-overlay.show {
            opacity: 1;
        }
        .custom-alert-overlay.show .custom-alert-box {
            transform: scale(1) translateY(0);
        }
        .custom-alert-icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            background: rgba(255, 244, 79, 0.1);
            border-radius: 50%;
            color: #FFF44F;
            margin-bottom: 20px;
            animation: pulse-alert 2s infinite;
        }
        @keyframes pulse-alert {
            0% { box-shadow: 0 0 0 0 rgba(255, 244, 79, 0.35); }
            70% { box-shadow: 0 0 0 10px rgba(255, 244, 79, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 244, 79, 0); }
        }
        .custom-alert-title {
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 12px 0;
            letter-spacing: 0.5px;
        }
        .custom-alert-msg {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            line-height: 1.6;
            margin: 0 0 26px 0;
            word-break: break-word;
        }
        .custom-alert-btn {
            background: #FFF44F;
            color: #111111;
            border: none;
            border-radius: 30px;
            padding: 11px 36px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(255, 244, 79, 0.2);
            outline: none;
        }
        .custom-alert-btn:hover {
            background: #e6d93f;
            transform: scale(1.04);
            box-shadow: 0 6px 20px rgba(255, 244, 79, 0.3);
        }
        .custom-alert-btn:active {
            transform: scale(0.98);
        }
    `;
    document.head.appendChild(style);

    // Override global window.alert
    window.alert = function (message) {
        // Prevent stacking alerts
        if (document.querySelector('.custom-alert-overlay')) return;

        const overlay = document.createElement('div');
        overlay.className = 'custom-alert-overlay';

        const box = document.createElement('div');
        box.className = 'custom-alert-box';

        const iconWrap = document.createElement('div');
        iconWrap.className = 'custom-alert-icon-wrap';

        // Premium SVG Warning Triangle Icon
        iconWrap.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        `;

        const title = document.createElement('h3');
        title.className = 'custom-alert-title';
        title.innerText = 'Notification';

        const msg = document.createElement('p');
        msg.className = 'custom-alert-msg';
        msg.innerText = message;

        const btn = document.createElement('button');
        btn.className = 'custom-alert-btn';
        btn.innerText = 'OK';

        // Assemble
        box.appendChild(iconWrap);
        box.appendChild(title);
        box.appendChild(msg);
        box.appendChild(btn);
        overlay.appendChild(box);
        document.body.appendChild(overlay);

        // Focus the button
        btn.focus();

        // Show animation
        setTimeout(() => {
            overlay.classList.add('show');
        }, 20);

        const closeAlert = () => {
            overlay.classList.remove('show');
            setTimeout(() => {
                overlay.remove();
            }, 250);
        };

        // Close events
        btn.addEventListener('click', closeAlert);

        const keyHandler = (e) => {
            if (e.key === 'Enter' || e.key === 'Escape') {
                e.preventDefault();
                closeAlert();
                document.removeEventListener('keydown', keyHandler);
            }
        };
        document.addEventListener('keydown', keyHandler);
    };

    // Override global window.customConfirm
    window.customConfirm = function(message) {
        return new Promise((resolve) => {
            // Prevent stacking alerts/confirms
            if (document.querySelector('.custom-alert-overlay')) {
                resolve(false);
                return;
            }

            const overlay = document.createElement('div');
            overlay.className = 'custom-alert-overlay';

            const box = document.createElement('div');
            box.className = 'custom-alert-box';

            const iconWrap = document.createElement('div');
            iconWrap.className = 'custom-alert-icon-wrap';
            
            // Premium SVG Question/Confirm Icon
            iconWrap.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            `;

            const title = document.createElement('h3');
            title.className = 'custom-alert-title';
            title.innerText = 'Confirmation';

            const msg = document.createElement('p');
            msg.className = 'custom-alert-msg';
            msg.innerText = message;

            const btnContainer = document.createElement('div');
            btnContainer.style.cssText = `
                display: flex;
                gap: 12px;
                justify-content: center;
                margin-top: 24px;
            `;

            const cancelBtn = document.createElement('button');
            cancelBtn.innerText = 'Cancel';
            cancelBtn.style.cssText = `
                background: transparent;
                color: #ffffff;
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 30px;
                padding: 11px 24px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
                flex: 1;
                outline: none;
            `;

            const confirmBtn = document.createElement('button');
            confirmBtn.innerText = 'OK';
            confirmBtn.style.cssText = `
                background: #FFF44F;
                color: #111111;
                border: none;
                border-radius: 30px;
                padding: 11px 24px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
                box-shadow: 0 4px 15px rgba(255, 244, 79, 0.2);
                flex: 1;
                outline: none;
            `;

            // Hover effects
            cancelBtn.addEventListener('mouseenter', () => {
                cancelBtn.style.background = 'rgba(255, 255, 255, 0.05)';
            });
            cancelBtn.addEventListener('mouseleave', () => {
                cancelBtn.style.background = 'transparent';
            });

            // Assemble
            btnContainer.appendChild(cancelBtn);
            btnContainer.appendChild(confirmBtn);

            box.appendChild(iconWrap);
            box.appendChild(title);
            box.appendChild(msg);
            box.appendChild(btnContainer);
            overlay.appendChild(box);
            document.body.appendChild(overlay);

            // Focus confirm button
            confirmBtn.focus();

            // Show animation
            setTimeout(() => {
                overlay.classList.add('show');
            }, 20);

            const closeConfirm = (val) => {
                overlay.classList.remove('show');
                setTimeout(() => {
                    overlay.remove();
                    resolve(val);
                }, 250);
            };

            cancelBtn.addEventListener('click', () => closeConfirm(false));
            confirmBtn.addEventListener('click', () => closeConfirm(true));

            const keyHandler = (e) => {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeConfirm(false);
                    document.removeEventListener('keydown', keyHandler);
                }
            };
            document.addEventListener('keydown', keyHandler);
        });
    };
})();
