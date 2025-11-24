$(document).ready(function () {
    const defaultFontSize = 20; // Aumentando el tamaño de la fuente

    // Obtener datos de género
    $.ajax({
        url: '../controlador/DashboardController.php',
        type: 'POST',
        data: {funcion: 'obtenerDatosGenero'},
        success: function(response) {
            const datos = JSON.parse(response);
            const labels = Object.keys(datos);
            const data = Object.values(datos);

            var ctxGender = document.getElementById('genderChart').getContext('2d');
            var genderChart = new Chart(ctxGender, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: ['#36A2EB', '#FF6384']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                font: {
                                    size: defaultFontSize
                                }
                            }
                        },
                        tooltip: {
                            bodyFont: {
                                size: defaultFontSize
                            },
                            titleFont: {
                                size: defaultFontSize
                            }
                        }
                    }
                }
            });
        },
        error: function(xhr, status, error) {
            console.log(error);
        }
    });

    // Obtener datos de títulos
    $.ajax({
        url: '../controlador/DashboardController.php',
        type: 'POST',
        data: {funcion: 'obtenerDatosTitulo'},
        success: function(response) {
            const datos = JSON.parse(response);
            const labels = Object.keys(datos);
            const data = Object.values(datos);

            var ctxTitle = document.getElementById('titleChart').getContext('2d');
            var titleChart = new Chart(ctxTitle, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Cantidad de Egresados',
                        data: data,
                        backgroundColor: '#36A2EB'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                font: {
                                    size: defaultFontSize
                                }
                            }
                        },
                        tooltip: {
                            bodyFont: {
                                size: defaultFontSize
                            },
                            titleFont: {
                                size: defaultFontSize
                            }
                        },
                    },
                    scales: {
                        x: {
                            ticks: {
                                font: {
                                    size: defaultFontSize
                                }
                            }
                        },
                        y: {
                            ticks: {
                                font: {
                                    size: defaultFontSize
                                }
                            }
                        }
                    }
                }
            });
        },
        error: function(xhr, status, error) {
            console.log(error);
        }
    });

    // Obtener datos de gestión
    $.ajax({
        url: '../controlador/DashboardController.php',
        type: 'POST',
        data: {funcion: 'obtenerDatosGestion'},
        success: function(response) {
            const datos = JSON.parse(response);
            const labels = Object.keys(datos);
            const data = Object.values(datos);

            var ctxGestion = document.getElementById('gestionChart').getContext('2d');
            var gestionChart = new Chart(ctxGestion, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Cantidad de Egresados',
                        data: data,
                        backgroundColor: '#4BC0C0'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                font: {
                                    size: defaultFontSize
                                }
                            }
                        },
                        tooltip: {
                            bodyFont: {
                                size: defaultFontSize
                            },
                            titleFont: {
                                size: defaultFontSize
                            }
                        },
                    },
                    scales: {
                        x: {
                            ticks: {
                                font: {
                                    size: defaultFontSize
                                }
                            }
                        },
                        y: {
                            ticks: {
                                font: {
                                    size: defaultFontSize
                                }
                            }
                        }
                    }
                }
            });
        },
        error: function(xhr, status, error) {
            console.log(error);
        }
    });

    // Obtener datos de fallecidos
    $.ajax({
        url: '../controlador/DashboardController.php',
        type: 'POST',
        data: {funcion: 'obtenerDatosFallecidos'},
        success: function(response) {
            const datos = JSON.parse(response);
            const labels = Object.keys(datos);
            const data = Object.values(datos);

            var ctxFallecidos = document.getElementById('fallecidosChart').getContext('2d');
            var fallecidosChart = new Chart(ctxFallecidos, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: ['#FF6384', '#FFCE56']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                font: {
                                    size: defaultFontSize
                                }
                            }
                        },
                        tooltip: {
                            bodyFont: {
                                size: defaultFontSize
                            },
                            titleFont: {
                                size: defaultFontSize
                            }
                        },
                    }
                }
            });
        },
        error: function(xhr, status, error) {
            console.log(error);
        }
    });

    // Obtener datos de año de graduación
    $.ajax({
        url: '../controlador/DashboardController.php',
        type: 'POST',
        data: {funcion: 'obtenerDatosGraduacion'},
        success: function(response) {
            const datos = JSON.parse(response);
            const labels = Object.keys(datos);
            const data = Object.values(datos);

            var ctxGraduacion = document.getElementById('graduacionChart').getContext('2d');
            var graduacionChart = new Chart(ctxGraduacion, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Cantidad de Egresados',
                        data: data,
                        borderColor: '#FF6384',
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                font: {
                                    size: defaultFontSize
                                }
                            }
                        },
                        tooltip: {
                            bodyFont: {
                                size: defaultFontSize
                            },
                            titleFont: {
                                size: defaultFontSize
                            }
                        },
                    },
                    scales: {
                        x: {
                            ticks: {
                                font: {
                                    size: defaultFontSize
                                }
                            }
                        },
                        y: {
                            ticks: {
                                font: {
                                    size: defaultFontSize
                                }
                            }
                        }
                    }
                }
            });
        },
        error: function(xhr, status, error) {
            console.log(error);
        }
    });
});
