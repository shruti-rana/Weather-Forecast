const API_KEY = 'c2909248f7084aa0acd74102250210';
const defaultCity = "Los Angeles";
let chart;

// Sidebar activated navigation
const sidebarLinks = document.querySelectorAll('.sidebar-link');
const contentSections = {
  dashboardSection: document.getElementById('dashboardSection'),
  calendarSection: document.getElementById('calendarSection'),
  aboutSection: document.getElementById('aboutSection'),
  notificationsSection: document.getElementById('notificationsSection'),
};

sidebarLinks.forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const section = link.dataset.section;
    if (!section) return;
    sidebarLinks.forEach(l => l.classList.remove('active'));
    link.classList.add('active');
    for (const key in contentSections) {
      contentSections[key].style.display = (key === section) ? '' : 'none';
    }
    if (section === 'calendarSection') {
      renderCalendar(currentMonth, currentYear);
    }
  });
});

const cityEl = document.getElementById('city');
const datetimeEl_dayname = document.querySelector('#datetime .dayname');
const datetimeEl_date = document.querySelector('#datetime .date');
const datetimeEl_time = document.querySelector('#datetime .time');
const temperatureEl = document.getElementById('temperature');
const descEl = document.getElementById('desc');
const pressureEl = document.getElementById('pressure');
const dewPointEl = document.getElementById('dewPoint');
const humidityEl = document.getElementById('humidity');
const windEl = document.getElementById('wind');
const windSpeedEl = document.getElementById('windSpeed');
const rainChanceEl = document.getElementById('rainChance');
const pressureCardEl = document.getElementById('pressureCard');
const weekDaysEl = document.getElementById('weekDays');
const weatherIconEl = document.getElementById('weatherIcon');

const themeToggleBtn = document.getElementById('themeToggleBtn');
const searchInput = document.getElementById('search');
const suggestionsList = document.getElementById('suggestionsList');

themeToggleBtn.onclick = () => {
  const body = document.body;
  if(body.classList.contains('light-theme')){
    body.classList.replace('light-theme', 'dark-theme');
    themeToggleBtn.textContent = '🌙';
  } else {
    body.classList.replace('dark-theme', 'light-theme');
    themeToggleBtn.textContent = '☀️';
  }
};

let autocompleteTimeout = null;
searchInput.addEventListener('input', () => {
  const query = searchInput.value.trim();
  clearTimeout(autocompleteTimeout);
  if (!query) {
    suggestionsList.innerHTML = "";
    suggestionsList.style.display = "none";
    return;
  }
  autocompleteTimeout = setTimeout(() => {
    fetch(`https://api.weatherapi.com/v1/search.json?key=${API_KEY}&q=${encodeURIComponent(query)}`)
      .then(resp => resp.json())
      .then(data => {
        suggestionsList.innerHTML = "";
        if (Array.isArray(data) && data.length) {
          data.forEach(city => {
            const li = document.createElement('li');
            li.textContent = `${city.name}${city.region ? `, ${city.region}` : ''}, ${city.country}`;
            li.dataset.city = city.name;
            li.tabIndex = 0;
            li.onclick = () => {
              pickSuggestion(city.name);
            };
            li.onmousedown = e => e.preventDefault();
            suggestionsList.appendChild(li);
          });
          suggestionsList.style.display = "";
        } else {
          suggestionsList.style.display = "none";
        }
      })
      .catch(() => {
        suggestionsList.innerHTML = "";
        suggestionsList.style.display = "none";
      });
  }, 250);
});

searchInput.addEventListener('keydown', e => {
  if (e.key === "Enter" && suggestionsList.children.length > 0) {
    e.preventDefault();
    suggestionsList.children[0].click();
  }
});

searchInput.addEventListener('blur', () => {
  setTimeout(() => {
    suggestionsList.innerHTML = "";
    suggestionsList.style.display = "none";
  }, 140);
});

function pickSuggestion(cityName) {
  searchInput.value = cityName;
  suggestionsList.innerHTML = "";
  suggestionsList.style.display = "none";
  updateWeather(cityName);
}

async function fetchForecast(city) {
  const url = `https://api.weatherapi.com/v1/forecast.json?key=${API_KEY}&q=${encodeURIComponent(city)}&days=7&aqi=no&alerts=no`;
  const resp = await fetch(url);
  if (!resp.ok) throw new Error("City not found");
  return resp.json();
}

function updateDateTimeDisplay(localtimeStr){
  let dt = new Date(localtimeStr.replace(' ', 'T'));
  if(isNaN(dt)) dt = new Date();
  const optionsDate = { year:'numeric', month:'short', day:'numeric' };
  const optionsDay = { weekday:'long' };
  if (datetimeEl_dayname) datetimeEl_dayname.textContent = dt.toLocaleDateString(undefined, optionsDay);
  if (datetimeEl_date) datetimeEl_date.textContent = dt.toLocaleDateString(undefined, optionsDate);
  function pad(num){ return num <10 ? '0'+num : num;}
  let h = pad(dt.getHours());
  let m = pad(dt.getMinutes());
  let s = pad(dt.getSeconds());
  if (datetimeEl_time) datetimeEl_time.textContent = `${h}:${m}:${s}`;
}

async function updateWeather(city) {
  try {
    if (cityEl) cityEl.textContent = "Loading...";
    const data = await fetchForecast(city);

    if (cityEl) cityEl.textContent = data.location.name;
    if (temperatureEl) temperatureEl.textContent = `${Math.round(data.current.temp_c)}°`;
    if (descEl) descEl.textContent = data.current.condition.text;
    const pressureValue = `${data.current.pressure_mb} hpa`;
    if (pressureEl) pressureEl.textContent = pressureValue;
    if (pressureCardEl) pressureCardEl.textContent = pressureValue;
    if (dewPointEl) dewPointEl.textContent = `Dew Point: ${Math.round(data.current.dewpoint_c)}°C`;
    if (humidityEl) humidityEl.textContent = `${data.current.humidity}% humidity`;
    if (windEl) windEl.textContent = `${Math.round(data.current.wind_kph)} km/h wind`;
    if (windSpeedEl) windSpeedEl.textContent = `${Math.round(data.current.wind_kph)} km/h`;
    updateDateTimeDisplay(data.location.localtime);
    if (rainChanceEl) rainChanceEl.textContent = data.forecast.forecastday[0].day.daily_chance_of_rain + '%';

    const iconUrl = `https:${data.current.condition.icon}`;
    if (weatherIconEl) {
      weatherIconEl.src = iconUrl;
      weatherIconEl.alt = data.current.condition.text;
    }

    if (weekDaysEl) weekDaysEl.innerHTML = "";
    if (data.forecast?.forecastday?.length && weekDaysEl) {
      data.forecast.forecastday.forEach(d => {
        const date = new Date(d.date);
        const day = date.toLocaleDateString(undefined, { weekday: 'short' });
        const iconDay = `https:${d.day.condition.icon}`;
        const temp = Math.round(d.day.avgtemp_c);

        weekDaysEl.innerHTML += `
          <div class="day-card">
            <div class="day">${day}</div>
            <div class="icon"><img src="${iconDay}" width="40" height="40" alt="${d.day.condition.text}" /></div>
            <div class="temp">${temp}°</div>
          </div>
        `;
      });
      const today = data.forecast.forecastday[0].day;
      const temps = [today.mintemp_c, today.avgtemp_c, today.maxtemp_c, today.avgtemp_c];
      renderTempChart(temps);
    } else if (weekDaysEl) {
      weekDaysEl.innerHTML = "";
      renderTempChart([0, 0, 0, 0]);
    }
  } catch (e) {
    if (cityEl) cityEl.textContent = "City not found";
    if (rainChanceEl) rainChanceEl.textContent = "—";
    if (dewPointEl) dewPointEl.textContent = "-";
    if (pressureEl) pressureEl.textContent = "-";
    if (pressureCardEl) pressureCardEl.textContent = "-";
    if (weekDaysEl) weekDaysEl.innerHTML = "";
    renderTempChart([0, 0, 0, 0]);
    alert(e.message);
  }
}

function renderTempChart(temps) {
  const chartCanvas = document.getElementById('tempChart');
  if (!chartCanvas) return;
  const ctx = chartCanvas.getContext('2d');
  if (chart) chart.destroy();
  chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['Morning', 'Day', 'Evening', 'Night'],
      datasets: [{
        data: temps,
        label: "Temp (°C)",
        fill: true,
        borderColor: "#42598c",
        backgroundColor: "rgba(100,150,255,0.06)",
        tension: 0.5
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: false, grid: { color: "#eef3fa" } },
        x: { grid: { display: false } }
      },
      responsive: false,
      maintainAspectRatio: false,
    }
  });
}

const calendarMonthLabel = document.getElementById('calendarMonth');
const calendarBody = document.getElementById('calendarBody');
const calendarDaysHeader = document.getElementById('calendarDaysHeader');
const prevMonthBtn = document.getElementById('prevMonthBtn');
const nextMonthBtn = document.getElementById('nextMonthBtn');
const daysOfWeek = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
const today = new Date();
let currentYear = today.getFullYear();
let currentMonth = today.getMonth();


// Initial load:
updateWeather(defaultCity);

sidebarLinks.forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const section = link.dataset.section;
    if (!section) return;
    sidebarLinks.forEach(l => l.classList.remove('active'));
    link.classList.add('active');
    for (const key in contentSections) {
      contentSections[key].style.display = (key === section) ? '' : 'none';
    }
    if (section === 'calendarSection') {
      renderCalendar(currentMonth, currentYear);
    }
    if (section === 'notificationsSection') {
      loadNotifications();
    }
  });
});
async function loadNotifications() {
  try {
    const city = cityEl.textContent || defaultCity; 
    const url = `https://api.weatherapi.com/v1/forecast.json?key=${API_KEY}&q=${encodeURIComponent(city)}&days=1&alerts=yes`;
    const resp = await fetch(url);
    if (!resp.ok) throw new Error("Failed to fetch notifications");
    const data = await resp.json();

    const alertsEl = document.getElementById('weatherAlerts');
    const summaryEl = document.getElementById('dailySummary');

    // Weather Alerts
    if (data.alerts && data.alerts.alert && data.alerts.alert.length > 0) {
      alertsEl.innerHTML = '<h3>Weather Alerts</h3>' + data.alerts.alert.map(alert => `
        <p><strong>${alert.headline}</strong>: ${alert.desc}</p>
      `).join('');
    } else {
      alertsEl.innerHTML = '<h3>Weather Alerts</h3><p>No active weather alerts at this time. Stay safe!</p>';
    }

    // Daily Forecast Summary
    const forecastDay = data.forecast.forecastday[0].day;
    summaryEl.innerHTML = `
      <h3>Daily Forecast Summary</h3>
      <p>Average Temperature: ${forecastDay.avgtemp_c}°C</p>
      <p>Chance of Rain: ${forecastDay.daily_chance_of_rain}%</p>
      <p>Max Wind Speed: ${forecastDay.maxwind_kph} km/h</p>
    `;

  } catch (error) {
    document.getElementById('weatherAlerts').innerHTML = '<h3>Weather Alerts</h3><p>Error loading alerts.</p>';
    document.getElementById('dailySummary').innerHTML = '<h3>Daily Forecast Summary</h3><p>Error loading summary.</p>';
    console.error(error);
  }
}

// Sidebar navigation (replace multi-listener blocks with one clear block)
sidebarLinks.forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const section = link.dataset.section;
    if (!section) return;
    sidebarLinks.forEach(l => l.classList.remove('active'));
    link.classList.add('active');
    for (const key in contentSections) {
      contentSections[key].style.display = (key === section) ? '' : 'none';
    }
    if (section === 'calendarSection') {
      renderCalendar(currentMonth, currentYear);
    }
    if (section === 'notificationsSection') {
      loadNotifications(); // Now loads notifications whenever this section opens
    }
  });
});




